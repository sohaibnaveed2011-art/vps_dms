<?php

namespace App\Services\Voucher;

use App\Models\Voucher\PosSession;

class PosService
{
    public function openSession(array $data): PosSession
    {
        return PosSession::create($data);
    }

    public function closeSession(PosSession $session, array $data): PosSession
    {
        $session->update($data);

        return $session;
    }
}
