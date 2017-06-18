<?php

// importar classes para scope
use Bioliving\Database\Db as Db;
use Bioliving\Errors\Errors as Errors;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

/////////////////POST tags//////////////////////////
$app->post('/api/tags/add', function (Request $request, Response $response) {
    $tag = $request->getParam('nomeTag');
    $error = array();
    $minCar = 1;  //valor minimo de caracteres da tag
    $maxCar = 45; //valor maximo de caracteres da tag

    //verificar se tag existe
    $sql = "SELECT * FROM tags WHERE tag_nome = :nome ";

    try {
        // Get DB object
        $db = new db();
        //connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':nome', $tag);
        $stmt->execute();
        $db = null;
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($dados)) {
            $responseData = [
                'Resposta' => "Tag já existe!"
            ];
            return $response
                ->withJson($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);

        } else {
            if (!v::stringType()->length($minCar, $maxCar)->validate($tag)) $error[] = "Insira uma tag com pelo menos $minCar caracteres e no maximo $maxCar";
            elseif (!v::alnum()->validate($tag) || !v::noWhitespace()->validate($tag)) $error[] = "Tag só pode conter numeros e letras";

            if (count($error) === 0) {

                //buscar db todos os customers
                $sql = "INSERT INTO tags (tag_nome) VALUES  (:nome)";

                try {
                    // Get DB object
                    $db = new db();
                    //connect
                    $db = $db->connect();
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':nome', $tag);
                    $stmt->execute();
                    $db = null;
                    $responseData = [
                        'Resposta' => "Tag adicionada com sucesso!"
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


        }


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
});