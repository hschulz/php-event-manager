<?php

declare(strict_types=1);

namespace Hschulz\Event;

use function array_key_exists;
use function call_user_func;
use Hschulz\DataStructures\Queue\PriorityQueue;
use function is_callable;

/**
 * Description of AbstractEventManager
 */
class AbstractEventManager implements EventManager
{
    /**
     *
     * @var array<PriorityQueue>
     */
    protected array $events = [];

    /**
     *
     */
    public function __construct()
    {
        $this->events = [];
    }

    /**
     * Attaches a listener to an event
     *
     * @param string $event The event name
     * @param callable $callback The method or function executed
     * @param int $priority The execution order for multiple callbacks
     * @void
     */
    public function attach(string $event, callable $callback, int $priority = self::PRIORITY_MIN): void
    {
        /* If there aren't any listeners for an event yet */
        if (empty($this->events[$event])) {

            /* Create a new queue for the event. */
            $this->events[$event] = new PriorityQueue();
        }

        /* Create a new listener with the given data */
        $listener = new EventListener($event, $callback, $priority);

        /* Insert the new callback into the queue. */
        $this->events[$event]->insert($listener, $priority);
    }

    /**
     * Detaches a listener from an event
     *
     * @param string $event the event to attach too
     * @param callable $callback a callable function
     * @return bool true on success false on failure
     */
    public function detach(string $event, callable $callback): bool
    {
        return false;
    }

    /**
     * Trigger an event
     *
     * Can accept an Event or will create one if not passed
     *
     * @param  Event $event
     * @return ResponseCollection
     */
    public function trigger(Event $event): ResponseCollection
    {
        $response = new ResponseCollection();

        $queue = $this->getListeners($event->getName());

        $queue->merge($this->getListeners(Event::EVENT_ALL));

        foreach ($queue as $listener) {
            /* @var $listener EventListener */

            $callback = $listener->getCallback();

            if (empty($callback) || !is_callable($callback)) {
                // @todo detach listener
                continue;
            }

            $response->push(
                call_user_func($callback, $event)
            );

            if ($event->isPropagationStopped()) {
                break;
            }
        }

        return $response;
    }

    /**
     * Clear all listeners for a given event name.
     *
     * @param  string $event
     * @return void
     */
    public function clearListeners(string $event): void
    {
        unset($this->events[$event]);
    }

    /**
     *
     * @param string $event An event name
     * @return PriorityQueue
     */
    public function getListeners(string $event): PriorityQueue
    {
        return array_key_exists($event, $this->events)
                ? clone $this->events[$event]
                : new PriorityQueue();
    }
}
