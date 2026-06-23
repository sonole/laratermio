<?php

namespace App\Filament\Pages;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        $result = parent::handleRecordUpdate($record, $data);

        if (array_key_exists('password', $data) && $record instanceof User && $record->must_change_password) {
            $record->updateQuietly(['must_change_password' => false]);
        }

        return $result;
    }

    protected function getRedirectUrl(): ?string
    {
        $user = $this->getUser();

        if ($user instanceof User && ! $user->must_change_password) {
            return session()->pull('url.intended_after_password_change');
        }

        return null;
    }
}
