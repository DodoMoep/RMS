<?php

namespace App\Exceptions;

use DomainException;

/**
 * Fachlich begruendeter Fehler, dessen Text an Benutzer gehen darf.
 *
 * Die Ausnahme traegt einen Uebersetzungsschluessel und liefert den Text ueber
 * userMessage(). getMessage() liefert den Schluessel und eignet sich fuer Logs. Alle
 * anderen Ausnahmen werden geloggt und dem Benutzer nur als allgemeine Meldung angezeigt.
 */
abstract class UserMessageException extends DomainException
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public function __construct(public readonly string $translationKey, public readonly array $replace = [])
    {
        parent::__construct($translationKey);
    }

    public function userMessage(): string
    {
        return __($this->translationKey, $this->replace);
    }
}
