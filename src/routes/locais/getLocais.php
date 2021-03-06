<?php
use Bioliving\Database\Db as Db;
use Bioliving\Errors\Errors as Errors;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;



//////////Receber todos os locais/Pesquisar em todos os locais/////////////
// Necessita de latitude e longitude para pesquisar
# Variaveis alteráveis:
#   min: 1, max: 10
# Exemplo: /api/locais?page=1&results=2&by=nome&order=DESC&msg=henrique&id=9
$app->get('/locais', function (Request $request, Response $response) {

    $byArr = [
        'id' => 'localizacao',
        'nome' => 'nome',
        'lat' => 'lat',
        'lng' => 'lng'
    ]; // Valores para ordernar por, fizemos uma array para simplificar queries

    $maxResults = 10; // maximo de resultados por pagina
    $minResults = 1; // minimo de resultados por pagina
    $byDefault = 'id'; // order by predefinido
    $paginaDefault = 1; // pagina predefenida
    $orderDefault = "ASC"; //ordenação predefenida
    $msgDefault = "";
    $idDefault = 0;


    $parametros = $request->getQueryParams(); // obter parametros do querystring
    $page = isset($parametros['page']) ? (int)$parametros['page'] : $paginaDefault;
    $results = isset($parametros['results']) ? (int)$parametros['results'] : $maxResults;
    $by = isset($parametros['by']) ? $parametros['by'] : $byDefault;
    $order = isset($parametros['order']) ? $parametros['order'] : $orderDefault;
    $id = isset($parametros['id']) ? (int)$parametros['id'] : $idDefault;
    $msg = isset($parametros['msg']) ? $parametros['msg'] : $msgDefault;
    $minLat = isset($parametros['minLat']) ? $parametros['minLat'] : false;
    $maxLat = isset($parametros['maxLat']) ? $parametros['maxLat'] : false;
    $minLng = isset($parametros['minLng']) ? $parametros['minLng'] : false;
    $maxLng = isset($parametros['maxLng']) ? $parametros['maxLng'] : false;

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

    if (!validaLatLng('latitude', $minLat)) $minLat = false;
    if (!validaLatLng('latitude', $maxLat)) $maxLat = false;
    if (!validaLatLng('longitude', $minLng)) $minLng = false;
    if (!validaLatLng('longitude', $maxLng)) $maxLng = false;


    if ($minLat != false & $maxLat != false & $minLng != false & $maxLng != false) {


        if ($page > 0 && $results > 0) {
            //definir numero de resultados
            //caso request tenha parametros superiores ao numero máximo permitido então repor com o valor maximo permitido e vice-versa
            $results = $results > $maxResults ? $maxResults : $results; //se o querystring results for maior que o valor maximo definido passa a ser esse valor maximo definido
            $results = $results < $minResults ? $minResults : $results; //se o querystring results for menor que o valor minimo definido passa a ser esse valor minimo definido
            //caso tenha parametros diferentes de "ASC" ou "DESC" então repor com o predefinido
            $order = $order == "ASC" || $order == "DESC" ? $order : $orderDefault;
            //order by se existe como key no array, caso nao repor com o predefenido
            $by = array_key_exists($by, $byArr) ? $by : $byDefault;

            // A partir de quando seleciona resultados
            $limitNumber = ($page - 1) * $results;
            $passar = $byArr[$by];


            if ($msg != $msgDefault && $id != $idDefault) $extraWhere = " WHERE (localizacao = :id OR nome LIKE :msg) AND lat >= :minLat AND lat <= :maxLat
      AND lng >= :minLng AND lng <= :maxLng ";
            elseif ($msg != $msgDefault && $id == $idDefault) $extraWhere = " WHERE  nome LIKE :msg AND lat >= :minLat AND lat <= :maxLat
      AND lng >= :minLng AND lng <= :maxLng";
            elseif ($msg === $msgDefault && $id != $idDefault) $extraWhere = " WHERE localizacao = :id AND  lat >= :minLat AND MyLat <= :maxLat
      AND lng >= :minLng AND lng <= :maxLng";
            else$extraWhere = "WHERE lat >= :minLat AND lat <= :maxLat
      AND lng >= :minLng AND lng <= :maxLng ";


            if ($order == $orderDefault) {
                $sql = "SELECT * FROM `localizacao` " . $extraWhere . " ORDER BY $passar  LIMIT :limit , :results";
            } else {
                $sql = "SELECT * FROM `localizacao`" . $extraWhere . " ORDER BY $passar DESC LIMIT :limit , :results";

            }

            try {
                $responseData = "";
                $status = 200; // OK
                // iniciar ligação à base de dados
                $db = new Db();
                $msgEnv = "%$msg%";
                // conectar
                $db = $db->connect();
                $stmt = $db->prepare($sql);

                $stmt->bindValue(':limit', (int)$limitNumber, PDO::PARAM_INT);
                $stmt->bindValue(':results', (int)$results, PDO::PARAM_INT);
                $stmt->bindValue(':minLat', (int)$minLat, PDO::PARAM_INT);
                $stmt->bindValue(':maxLat', (int)$maxLat, PDO::PARAM_INT);
                $stmt->bindValue(':minLng', (int)$minLng, PDO::PARAM_INT);
                $stmt->bindValue(':maxLng', (int)$maxLng, PDO::PARAM_INT);
                if ($id != $idDefault) $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                if ($msg != $msgDefault) $stmt->bindValue(':msg', $msgEnv, PDO::PARAM_INT);
                $stmt->execute();
                $db = null;
                $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // remover nulls e strings vazias
                $dados = array_filter(array_map(function ($evento) {
                    return $evento = array_filter($evento, function ($coluna) {
                        return $coluna !== null && $coluna !== '';
                    });
                }, $dados));


                $dadosLength = (int)sizeof($dados);
                if ($dadosLength === 0) {
                    $responseData = [
                        "status" => 404,
                        "info" => 'pagina inexistente'
                    ]; // Page not found
                } else {
                    $status = 200;
                    $responseData = [
                        "status" => 200,
                        "info" => "Localizações no intervalo definido com sucesso!",
                        "data" => $dados

                    ];
                }


                return $response
                    ->withJson($responseData, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);


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
                "info" => 'Parametros invalidos'


            ];

            return $response
                ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
        }
    } else {
        $status = 422; // Unprocessable Entity
        $errorMsg = [

            "status" => "$status",
            "info" => 'Parametros invalidos'


        ];

        return $response
            ->withJson($errorMsg, $status, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

});