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

## How its works?

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

At this point you must have a mutation called `uploadFile` in your graphql schema, 
see [graphql-multipart-request-spec](https://github.com/jaydenseric/graphql-multipart-request-spec)
for details of using multipart form data to upload files.


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