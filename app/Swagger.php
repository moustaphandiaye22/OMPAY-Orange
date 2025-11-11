<?php

/**
 * @OA\Info(
 *     title="OMPAY Orange Money API",
 *     version="1.0.0",
 *     description="API pour l'application Orange Money - OMPAY",
 *     @OA\Contact(
 *         email="support@orangemoney.sn"
 *     )
 * ),
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * ),
 * @OA\Server(
 *     url="https://api.ompaysn.com/api",
 *     description="Serveur de production"
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Swagger
{
}