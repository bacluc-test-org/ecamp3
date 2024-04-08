<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Category;
use App\Entity\ContentNode\ColumnLayout;
use App\Entity\ContentType;
use App\State\Util\AbstractPersistProcessor;
use App\Util\EntityMap;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @template-extends AbstractPersistProcessor<Category>
 */
class CategoryCreateProcessor extends AbstractPersistProcessor {
    public function __construct(
        ProcessorInterface $decorated,
        private EntityManagerInterface $em,
    ) {
        parent::__construct($decorated);
    }

    /**
     * @param Category $data
     */
    public function onBefore($data, Operation $operation, array $uriVariables = [], array $context = []): Category {
        // TODO implement actual prototype cloning and strategy classes, this is just a dummy implementation to
        //      fill the non-nullable field for Doctrine
        $rootContentNode = new ColumnLayout();
        $rootContentNode->contentType = $this->em
            ->getRepository(ContentType::class)
            ->findOneBy(['name' => 'ColumnLayout'])
        ;
        $rootContentNode->data = ['columns' => [['slot' => '1', 'width' => 12]]];
        $data->setRootContentNode($rootContentNode);

        if (isset($data->copyCategorySource)) {
            // CopyActivity Source is set -> copy it's content (rootContentNode)
            $entityMap = new EntityMap();
            $rootContentNode->copyFromPrototype($data->copyCategorySource->getRootContentNode(), $entityMap);
        }

        return $data;
    }
}
