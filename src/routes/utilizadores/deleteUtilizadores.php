<?php
use Bioliving\Database\Db as Db;
use Bioliving\Errors\Errors as Errors;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Bioliving\Custom\Token as Token;

$app->delete('/utilizadores/{id}', function (Request $request, Response $response) {
    if (Token::validarScopes('admin')) {
        $id = (int)$request->getAttribute('id'); // ir buscar id
        if (is_int($id) && $id > 0) {
            $sql = "SELECT * FROM utilizadores WHERE id_utilizadores = :id";
            try {
                // Get DB object
                $db = new db();
                //connect
                $db = $db->connect();
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $db = null;
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($dados) >= 1) {

                    try {
                        // Get DB object
                        $db = new db();
                        //connect
                        $db = $db->connect();

                        $stmt = $db->prepare($sql);
                        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                        $stmt->execute();
                        $db = null;
                        $responseData = [
                            "status" => 200,
                            'info' => "Utilizador apagado com sucesso!"
                        ];

                        return $response
                            ->withJson($responseData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);


                    } catch (PDOException $err) {
                        $status = 503; // Service unavailable
                        // Primeiro callback chamado em ambiente de desenvolvimento, segundo em producao
                        $errorMsg = Errors::filtroReturn(function ($err) {
                            return [

                                "status" => $err->getCode(),
                                "info" => $err->getMessage()

                            ];
                        }, function () {
                            return [
                                "status" => 503,
                                "info" => 'Servico Indisponivel'
                            ];
                        }, $err);

                        return $response
                            ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
                    }
                } else {
                    $status = 422; // Unprocessable Entity
                    $errorMsg = [

                        "status" => "$status",
                        "info" => 'Utilizador não se encontra disponivel'


                    ];

                    return $response
                        ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
                }
            } catch (PDOException $err) {
                $status = 503; // Service unavailable
                // Primeiro callback chamado em ambiente de desenvolvimento, segundo em producao
                $errorMsg = Errors::filtroReturn(function ($err) {
                    return [

                        "status" => $err->getCode(),
                        "info" => $err->getMessage()

                    ];
                }, function () {
                    return [
                        "status" => 503,
                        "info" => 'Servico Indisponivel'
                    ];
                }, $err);

                return $response
                    ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
            }


        } else {
            $status = 422; // Unprocessable Entity
            $errorMsg = [

                "status" => "$status",
                "info" => 'Parametros inválidos'


            ];

            return $response
                ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        }

    } else {
        $status = 401;
        $errorMsg = [

            "status" => "$status",
            "info" => 'Acesso não autorizado'


        ];

        return $response
            ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

});