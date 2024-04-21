<?php


namespace App\Framework;


use App\Contracts\InteractionHandler;
use App\Framework\Enums\HandledInteractionType;
use App\Framework\InteractionHandlers\AbstractInteractionDriver;
use App\Framework\InteractionHandlers\ApplicationCommands\Drivers\AbstractSlashCommandsDriver;
use App\Framework\InteractionHandlers\ApplicationCommands\AbstractSlashCommand;
use App\Framework\InteractionHandlers\ApplicationCommands\Drivers\ISlashCommandsDriver;
use App\Framework\InteractionHandlers\ApplicationCommands\Drivers\SlashCommandsDriver;
use App\Framework\InteractionHandlers\MessageComponents\AbstractMessageComponent;
use App\Framework\InteractionHandlers\MessageComponents\Drivers\MessageComponentsDriver;
use App\Hephaestus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;
use Monolog\Level;
use ReflectionClass;

/**
 *
 */
class InteractionReflectionLoader
{

    public function __construct(
        public Hephaestus $hephaestus,
    ) {
    }

    public function make(Hephaestus $hephaestus): self
    {
        return new static($hephaestus);
    }

    public function loadAll()
    {
        foreach (HandledInteractionType::cases() as $type) {
            $this->load($type);
        }
    }

    public function load(HandledInteractionType $type): array
    {
        $key = Hephaestus::getHandlerCacheKey($type);

        $existing = Cache::get($key);
        if (is_null($existing)) {
            Cache::forever($key, $this->getClasses($type));
            return $this->load($type);
        }
        return collect($existing)
            ->unique()
            ->toArray();
    }

    public function getClasses(HandledInteractionType $type): array
    {
        $pathName = $this->resolvePathName($type);
        if (!File::isDirectory($pathName)) {
            Log::warning("No directory found for Handler Type {$type->name}, tried {$pathName}. Do the directory exists ?", [$type, $pathName]);
            return [];
        }

        return $this->extractClasses($type)
            ->unique()
            ->all();
    }

    public function makePathName(string $string)
    {
        return app_path("InteractionHandlers" . DIRECTORY_SEPARATOR . $string);
    }

    /**
     * Resolve
     */
    public function resolvePathName(HandledInteractionType $type)
    {
        return match ($type) {
            HandledInteractionType::APPLICATION_COMMAND                 =>  $this->makePathName("SlashCommands"),
            HandledInteractionType::APPLICATION_COMMAND_AUTOCOMPLETE    =>  $this->makePathName("Autocompletes"),
            HandledInteractionType::MESSAGE_COMPONENT                   =>  $this->makePathName("MessageComponents"),
            HandledInteractionType::MODAL_SUBMIT                        =>  $this->makePathName("ModalSubmits"),
            HandledInteractionType::PING                                =>  $this->makePathName("Pings"),
        };
    }

    /**
     *
     */
    public function resolveAbstraction(HandledInteractionType $type): mixed
    {
        return match ($type) {
            HandledInteractionType::APPLICATION_COMMAND                 =>  AbstractSlashCommand::class,
            HandledInteractionType::MESSAGE_COMPONENT                   =>  AbstractMessageComponent::class,
        };
    }

    /**
     * Extract classes from the provided application path.
     */
    public function extractClasses(HandledInteractionType $type): Collection
    {
        $path = $this->resolvePathName($type);
        // dd(File::allFiles($path), $path);
        $classes = collect(File::allFiles($path))
            ->map(function ($file) {
                $relativePath = str_replace(
                    Str::finish(app_path(), DIRECTORY_SEPARATOR),
                    '',
                    $file->getPathname()
                );

                $folders = Str::beforeLast(
                    $relativePath,
                    DIRECTORY_SEPARATOR
                ) . DIRECTORY_SEPARATOR;

                $className = Str::after($relativePath, $folders);

                $class = app()->getNamespace() . str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    $folders . $className
                );

                return $class;
            })
            ->filter(function ($strClass) use ($type) {
                $class = new ReflectionClass($strClass);

                return !$class->isAbstract()
                    && $class->implementsInterface(InteractionHandler::class)
                    && $class->isSubclassOf($this->resolveAbstraction($type));
            })
            ;
        $resolvedCount = count($classes);
        if (!$classes->count()) {
            $this->hephaestus->log("Empty path {$path}!", Level::Warning, [$path]);
        }

        $this->hephaestus->log("Found <fg=cyan>{$resolvedCount}</> classes meting filtering conditions in {$path}");

        return $classes;
    }

    public function bind(HandledInteractionType $type)
    {
        $this->hephaestus->log("Binding {$type->name}");
        switch ($type) {
            case HandledInteractionType::APPLICATION_COMMAND:
                /**
                 * @var AbstractSlashCommandsDriver $driver
                 */
                $driver = app(AbstractSlashCommandsDriver::class);
                $driver->register();

                break;
            default:
                $this->hephaestus->log("Cannot bind {$type->name}. Unimplementend.");
                break;
        }

        $classes = $this->load($type);
    }

    public function bindAll()
    {
        foreach (HandledInteractionType::cases() as $type) {
            $this->bind($type);
        }
        return $this;
    }


    // public function bindApplicationCommands()
    // {
    //     /**
    //      * @var SlashCommandsDriver
    //      */
    //     with(app(SlashCommandsDriver::class))
    //         ->register();
    // }

    /**
     *
     * @return Collection<InteractionHandler>
     */
    public function hydratedHandlers(HandledInteractionType $type): Collection
    {
        return collect($this->load($type))
            ->map(fn ($class) => app($class)); // Cast into appropriate container service
    }

    public function getDriver(HandledInteractionType $type) :  AbstractInteractionDriver|null
    {
        return match ($type) {
            HandledInteractionType::APPLICATION_COMMAND => app(ISlashCommandsDriver::class),
            HandledInteractionType::MESSAGE_COMPONENT   => app(MessageComponentsDriver::class),
            default => null,
        };
    }
}
