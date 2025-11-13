<?php

namespace App\Interfaces;

interface TransfertServiceInterface
{
    public function effectuerTransfert($utilisateur, $data);
    public function annulerTransfert($utilisateur, $idTransfert);
}