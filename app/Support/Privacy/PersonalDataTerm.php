<?php

namespace App\Support\Privacy;

use App\Support\Forms\PhoneNumber;

/**
 * Who NFR-PRIV-03 is about: the phone and/or the email a person gave us, in the form we store them
 * (E.164, lower case). Both are searched together, so one look finds a person who used the phone on
 * a lead form and the email for the newsletter.
 */
final readonly class PersonalDataTerm
{
    public function __construct(
        public ?string $phone,
        public ?string $email,
    ) {}

    /**
     * @return array{term: self|null, errors: array<string, string>}
     */
    public static function fromInput(?string $phone, ?string $email): array
    {
        $errors = [];
        $normalisedPhone = null;
        $normalisedEmail = null;

        if (trim((string) $phone) !== '') {
            $normalisedPhone = PhoneNumber::fromAnyCountry((string) $phone);

            if ($normalisedPhone === null) {
                $errors['phone'] = 'Enter a Bangladesh mobile such as 01712 345678, or any number with its +country code.';
            }
        }

        if (trim((string) $email) !== '') {
            $candidate = mb_strtolower(trim((string) $email));

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Enter a full email address, such as name@example.com.';
            } else {
                $normalisedEmail = $candidate;
            }
        }

        if ($errors === [] && $normalisedPhone === null && $normalisedEmail === null) {
            $errors['phone'] = 'Enter a phone number or an email address.';
        }

        return ['term' => $errors === [] ? new self($normalisedPhone, $normalisedEmail) : null, 'errors' => $errors];
    }

    /**
     * @return array{phone: string|null, email: string|null}
     */
    public function toArray(): array
    {
        return ['phone' => $this->phone, 'email' => $this->email];
    }
}
