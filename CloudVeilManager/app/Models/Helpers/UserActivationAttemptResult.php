<?php

namespace App\Models\Helpers;

class UserActivationAttemptResult
{
    const Success = 1;
    const ActivationLimitExceeded = 2;
    const AccountDisabled = 3;
    const GroupDisabled = 4;
    const IndentifyingInformationMissing = 5;
    const UnknownError = 6;

}
