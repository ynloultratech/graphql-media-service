<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Ynlo\GraphQLBundle\Behat\Context\ApiContext;
use Ynlo\GraphQLBundle\Model\ID;
use Ynlo\GraphQLMediaServiceBundle\Demo\AppBundle\Entity\File;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends ApiContext
{
    /**
     * @When /^upload file "([^"]*)"$/
     */
    public function uploadFile($arg1)
    {
        $query = <<<'GraphQL'
mutation ($file: Upload!) { 
    uploadFile(file: $file) {
        id 
        name
    } 
} 
GraphQL;

        $operations = ['query' => $query, 'variables' => ['file' => null]];

        $tmp = tempnam(sys_get_temp_dir(), 'upload');
        copy(__DIR__.'/files/'.$arg1, $tmp);

        $file = new UploadedFile($tmp, $arg1, null, null, null, true);
        $this->client->request(
            'post',
            '/',
            [
                'operations' => json_encode($operations),
                'map' => json_encode(['0' => ['variables.file']]),
            ],
            [$file],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
    }

    /**
     * @Then /^remove file "([^"]*)"$/
     */
    public function removeFile($arg1)
    {
        $id = ID::createFromString($arg1)->getDatabaseId();
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        /** @var  File $file */
        $file = $em->getReference(File::class, $id);

        $em->remove($file);
        $em->flush();
    }

    /**
     * @Then /^compare uploaded file "([^"]*)" with "([^"]*)"$/
     */
    public function compareUploadedFileWith($arg1, $arg2)
    {
        $arg1 = str_replace('http://localhost:8000', null, $arg1);
        $client = clone $this->client;
        $client->request('get', $arg1);
        $response = $client->getResponse();
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $uploadedContent = file_get_contents($response->getFile()->getPathname());
        } else {
            $uploadedContent = file_get_contents($this->getContainer()->getParameter('kernel.root_dir').'/../public'.$arg1);
        }

        $file = __DIR__.'/files/'.$arg2;
        $sourceContent = file_get_contents($file);
        \PHPUnit\Framework\Assert::assertEquals($sourceContent, $uploadedContent);
    }
}
