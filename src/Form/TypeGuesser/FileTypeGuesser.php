<?php
/**
 *  This file is part of the GraphQL Media Service package.
 *
 *  (c) YnloUltratech <support@ynloultratech.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Ynlo\GraphQLMediaServiceBundle\Form\TypeGuesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Ynlo\GraphQLBundle\Form\Type\IDType;
use Ynlo\GraphQLBundle\Form\TypeGuesser\DoctrineOrmTypeGuesser;
use Ynlo\GraphQLMediaServiceBundle\Model\FileInterface;

/**
 * Use ID as file type for FILE relations
 */
class FileTypeGuesser extends DoctrineOrmTypeGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        if (!$ret = $this->getMetadata($class)) {
            return null;
        }

        /** @var ClassMetadata $metadata */
        list($metadata) = $ret;

        if ($metadata->hasAssociation($property)) {
            $mapping = $metadata->getAssociationMapping($property);
            $target = $mapping['targetEntity'];

            if (is_subclass_of($target, FileInterface::class, true)) {
                return new TypeGuess(IDType::class, [], Guess::VERY_HIGH_CONFIDENCE);
            }
        }

        return null;
    }
}
