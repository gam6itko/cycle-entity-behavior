<?php

declare(strict_types=1);

namespace Cycle\ORM\Entity\Macros;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\Tuple;
use Cycle\ORM\Entity\Macros\Common\Dispatcher\Dispatcher;
use Cycle\ORM\Entity\Macros\Common\Dispatcher\ListenerProvider;
use Cycle\ORM\Entity\Macros\Common\Event\Mapper\Command\OnCreate;
use Cycle\ORM\Entity\Macros\Common\Event\Mapper\Command\OnUpdate;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDrivenCommandGenerator extends CommandGenerator
{
    private EventDispatcherInterface $eventDispatcher;

    // todo: add custom listener interface
    public function __construct(SchemaInterface $schema)
    {
        $listenerProvider = new ListenerProvider($schema);

        $this->eventDispatcher = new Dispatcher($listenerProvider);
    }

    protected function storeEntity(ORMInterface $orm, Tuple $tuple, bool $isNew): ?CommandInterface
    {
        if ($isNew) {
            $event = new OnCreate($tuple->node->getRole(), $tuple->mapper, $tuple->entity, $tuple->node, $tuple->state);
        } else {
            $event = new OnUpdate($tuple->node->getRole(), $tuple->mapper, $tuple->entity, $tuple->node, $tuple->state);
        }

        $event->command = parent::storeEntity($orm, $tuple, $isNew);

        $event = $this->eventDispatcher->dispatch($event);

        return $event->command;
    }

    protected function generateParentStoreCommand(
        ORMInterface $orm,
        Tuple $tuple,
        string $parentRole,
        bool $isNew
    ): ?CommandInterface {
        $parentMapper = $orm->getMapper($parentRole);

        $event = new OnCreate($parentRole, $parentMapper, $tuple->entity, $tuple->node, $tuple->state);
        $event->command = $isNew
            ? $parentMapper->queueCreate($tuple->entity, $tuple->node, $tuple->state)
            : $parentMapper->queueUpdate($tuple->entity, $tuple->node, $tuple->state);

        $event = $this->eventDispatcher->dispatch($event);

        return $event->command;
    }
}
