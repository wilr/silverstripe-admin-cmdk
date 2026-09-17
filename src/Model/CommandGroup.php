<?php

namespace Wilr\AdminCmdk\Model;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Security\Member;

/**
 * A named, ordered collection of {@see Command}s rendered as a single `Command.Group` in the
 * frontend palette, e.g. "Content", "Settings", "Recently viewed".
 */
class CommandGroup
{
    use Injectable;

    private string $code;

    private string $title;

    private int $priority = 0;

    /** @var array<string, Command> */
    private array $commands = [];

    public static function create(string $code, string $title, int $priority = 0): static
    {
        return new static($code, $title, $priority);
    }

    public function __construct(string $code, string $title, int $priority = 0)
    {
        if ($code === '') {
            throw new InvalidArgumentException('CommandGroup code cannot be empty');
        }

        $this->code = $code;
        $this->title = $title;
        $this->priority = $priority;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function addCommand(Command $command): static
    {
        $this->commands[$command->getCode()] = $command;

        return $this;
    }

    public function removeCommand(string $code): static
    {
        unset($this->commands[$code]);

        return $this;
    }

    public function getCommand(string $code): ?Command
    {
        return $this->commands[$code] ?? null;
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return array_values($this->commands);
    }

    /**
     * Commands in this group that the given member is allowed to view.
     *
     * @return Command[]
     */
    public function getViewableCommands(?Member $member): array
    {
        return array_values(array_filter(
            $this->commands,
            fn (Command $command) => $command->canView($member)
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Member $member): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'priority' => $this->priority,
            'commands' => array_map(
                fn (Command $command) => $command->toArray(),
                $this->getViewableCommands($member)
            ),
        ];
    }
}
