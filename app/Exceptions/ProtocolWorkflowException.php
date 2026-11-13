<?php

namespace App\Exceptions;

/**
 * Fachlich begruendeter Fehler bei Protokollen, z. B. zu viele Fotos oder eine ungueltige
 * Unterschrift. Verhalten siehe UserMessageException.
 */
class ProtocolWorkflowException extends UserMessageException {}
