<?php

declare(strict_types=1);

namespace Cycle\ORM\Entity\Macros\Timestamped;

use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Entity\Macros\Attribute\Listen;
use Cycle\ORM\Entity\Macros\Event\Mapper\Command\OnUpdate;

final class UpdatedAtListener
{
    public function __construct(
        private string $field = 'updatedAt'
    ) {
    }

    #[Listen(OnUpdate::class)]
    public function __invoke(OnUpdate $event): void
    {
        if ($event->command instanceof StoreCommandInterface) {
            $event->command->registerAppendix($this->field, new \DateTimeImmutable());
        }
    }
}
