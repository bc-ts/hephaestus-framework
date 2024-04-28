<?php

namespace Hephaestus\Framework\Contracts;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Hephaestus\Framework\DataTransferObjects\InteractionDTO;

interface InteractionHandler {

    /**
     * Handler for an interaction
     *
     * @return void
     */
    public function handle(InteractionDTO $interaction): void;

}
