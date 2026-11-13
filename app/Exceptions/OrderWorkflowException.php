<?php

namespace App\Exceptions;

/**
 * Fachlich begruendeter Fehler im Auftragsablauf, z. B. Packen in einem falschen Status.
 * Verhalten siehe UserMessageException.
 */
class OrderWorkflowException extends UserMessageException {}
