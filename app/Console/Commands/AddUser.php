<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('add:user')]
#[Description('Erstellt einen neuen Kunden-Account')]
class AddUser extends Command
{
    public function handle(): int
    {
        $name = $this->ask('Name');

        $email = $this->askValidated('E-Mail', function (string $value): ?string {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'Bitte eine gueltige E-Mail-Adresse eingeben.';
            }

            if (Customer::where('email', $value)->exists()) {
                return 'Diese E-Mail-Adresse ist bereits vergeben.';
            }

            return null;
        });

        $pw = $this->askPasswordValidated('Passwort', fn (string $v): ?string => strlen($v) < 8
            ? 'Das Passwort muss mindestens 8 Zeichen lang sein.'
            : null
        );

        Customer::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($pw),
        ]);

        $this->info("Kunde \"{$name}\" ({$email}) wurde erstellt.");

        return self::SUCCESS;
    }

    private function askValidated(string $label, \Closure $validate): string
    {
        while (true) {
            $value = $this->ask($label);
            $error = $validate((string) $value);

            if ($error === null) {
                return (string) $value;
            }

            $this->error($error);
        }
    }

    private function askPasswordValidated(string $label, \Closure $validate): string
    {
        while (true) {
            $value = $this->secret($label);
            $error = $validate((string) $value);

            if ($error === null) {
                return (string) $value;
            }

            $this->error($error);
        }
    }
}
