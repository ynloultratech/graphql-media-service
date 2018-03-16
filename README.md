# graphql-media-service

To build your application you probably need images, video's or maybe even a presentation too. 
The GraphQL Media Service handles all those media assets and centralizes them so you can find your content 
just the way you like it: fast and efficiently. 

- [X] Single endpoint to upload your files trough a API.
- [X] GraphQL Object for files to get details and download url.
- [X] Public and Private files using signed urls
- [X] Direct relations between files and entities

## Installation

Install using composer:

    composer require graphql-media-service
    
Add in your kernel:

````php
$bundles = [
    ...
    new Ynlo\GraphQLMediaService\MediaServiceBundle(),
    new AppBundle\AppBundle(),
];    
````

## Usage

> The following steps assume you have a configured GraphQLAPI using 
[graphql-bundle](https://github.com/ynloultratech/graphql-bundle).

Add the following config in your `config.yml`

````yaml
#config.yml

media_service:
    class: AppBundle\Entity\File
    default_storage: public_files
    storage:
       public_files:
          local:
              dir_name: "%kernel.root_dir%/../public/uploads"
              base_url: 'http://example.com/uploads'
````

> For performance reasons public files are served directly thought the http server, 
then the `base_url` must be a valid public accessible folder where the files are located.

Create a new entity `File`

````php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ynlo\GraphQLBundle\Annotation as GraphQL;
use Ynlo\GraphQLMediaService\Model\AbstractFile;

/**
 * @ORM\Entity()
 * @ORM\Table()
 *
 * @GraphQL\ObjectType()
 */
class File extends AbstractFile
{

}
````

Add the routing config in your `routing.yml`

````yaml
#...
media_service:
      resource: '@MediaServiceBundle/Resources/config/routing.yml'
      prefix:   /
````
> The routing is required to allow upload files using `/upload` action and fetch private
resources using signed urls. Can override or use your own config if you want.

Now yo can upload files using something like:

    curl -X POST -d 'hello' -H 'content-length: 5' -H 'content-type: text/plain' your_domain.com/upload

On success the server response with the id of the file:

````json
{
  "data": {
    "id": "RmlsZTpEMTdFODE5Ri0yM0I0LTQ2NkQtOTI3Qy03QjUwQTMxQ0I2QkY="
  }
}
````

Now can use the GraphQL API to get access to that file:

````graphql
query{
  node(id: "RmlsZTpEMTdFODE5Ri0yM0I0LTQ2NkQtOTI3Qy03QjUwQTMxQ0I2QkY="){
    id
    ... on File{
      name
      contentType
      size
      createdAt
      updatedAt
      url
    }
  }
}
````

At this point the media service is ready and can have access to upload files using the GraphQL API.

### Assign uploaded files to existent object

Upload files to the server is only the first step, 
you must able to link that files to existent objects. 
For example link a uploaded photo to the user profile.

Create a field to store the relation on a existent entity:

````php
/**
 * @ORM\Entity()
 * @ORM\Table()
 *
 * @GraphQL\ObjectType()
 */
class Profile implements NodeInterface
{
//....
/**
 * @var File
 *
 * @ORM\OneToOne(targetEntity="AppBundle\Entity\File", orphanRemoval=true)
 *
 * @GraphQL\Expose()
 *
 * @MediaService\AttachFile()
 */
protected $photo;

````

> Note the annotation `@MediaService\AttachFile()` is required on properties linked to
Files in order to resolve some parameters like the `url` in runtime.

@TODO ...