<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use Ynlo\GraphQLBundle\Behat\Context\ApiContext;
use Ynlo\GraphQLBundle\Model\ID;
use Ynlo\GraphQLMediaService\Demo\AppBundle\Entity\File;

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
        $file = __DIR__.'/files/'.$arg1;
        $this->client->request(
            'post',
            '/upload',
            ['name' => $arg1],
            [],
            [
                'CONTENT_TYPE' => 'text/plain',
                'HTTP_CONTENT_LENGTH' => filesize($file),
            ],
            file_get_contents($file)
        );
    }

    /**
     * @Given /^restart client$/
     */
    public function restartClient()
    {
        // required to fetch query after upload
        $this->client->restart();
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
        $this->client->request('get', $arg1);
        $response = $this->client->getResponse();
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $uploadedContent = file_get_contents($response->getFile()->getPathname());
        } else {
            $uploadedContent = file_get_contents($arg1);
        }

        $file = __DIR__.'/files/'.$arg2;
        $sourceContent = file_get_contents($file);
        \PHPUnit\Framework\Assert::assertEquals($sourceContent, $uploadedContent);
    }
}
