<?php

// importar classes para scope
use Bioliving\Database\Db as Db;
use Bioliving\Errors\Errors as Errors;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;


//////////////// POST localização//////////////////
$app->post('/api/eventos/localizacao/add', function (Request $request, Response $response) {
    $localizacao = $request->getParam('nomeLocalizacao');
    $lat = $request->getParam('lat');
    $lng = $request->getParam('lng');

    $error = array();
    $minCar = 1;
    $maxCar = 75;
    if (is_null($localizacao) || strlen($localizacao) < $minCar) {
        $error[] = array("nome" => "Insira um nome para o tipo de evento com mais que " . $minCar . " caracter. Este campo é obrigatório!");
    } elseif (strlen($localizacao) > $maxCar) {
        $error[] = array("nome" => "Nome excedeu limite máximo");
    }

    function validaLatLng($tipo, $valor)
    {
        $resultado = ($tipo == 'latitude')
            ? '/^(\+|-)?(?:90(?:(?:\.0{1,8})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,8})?))$/'
            : '/^(\+|-)?(?:180(?:(?:\.0{1,8})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,8})?))$/';

        if (preg_match($resultado, $valor)) {
            return true;
        } else {
            return false;
        }
    }

    if (validaLatLng('latitude', $lat) === false) {
        $error[] = array("nome" => "Latitude inválida");
    }

    if (validaLatLng('longitude', $lng) === false) {
        $error[] = array("nome" => "Longitude inválida");
    }

    if (count($error) === 0) {

        //buscar db todos os customers
        $sql = "INSERT INTO localizacao (lat,lng,nome) VALUES  (:lat,:lng,:nome)";
        try {
            // Get DB object
            $db = new db();
            //connect
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nome', $localizacao);
            $stmt->bindParam(':lat', $lat);
            $stmt->bindParam(':lng', $lng);
            $stmt->execute();
            $db = null;
            $responseData = [
                'Resposta' => "Localização adicionada com sucesso!"
            ];

            return $response
                ->withJson($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);


        } catch (PDOException $err) {
            $status = 503; // Service unavailable
            // Primeiro callback chamado em ambiente de desenvolvimento, segundo em producao
            $errorMsg = Errors::filtroReturn(function ($err) {
                return [
                    "error" => [
                        "status" => $err->getCode(),
                        "text" => $err->getMessage()
                    ]
                ];
            }, function () {
                return [
                    "error" => 'Servico Indisponivel'
                ];
            }, $err);

            return $response
                ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

        }
    } else {
        $status = 422; // Unprocessable Entity
        $errorMsg = [
            "error" => [
                "status" => "$status",
                "text" => [
                    $error
                ]

            ]
        ];

        return $response
            ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }
});
