<?php

/*
 * Middleware: https://github.com/tuupola/slim-jwt-auth
 * parses and authenticates a token when passed via header or cookie.
 */

use Bioliving\Custom\Token as Token;
use Bioliving\Errors\Errors as Errors;

// Middleware para autenticação de token
$app->add( new \Slim\Middleware\JwtAuthentication( [

		'secret' => getenv( 'SECRET_KEY' ),

		"path" => "/api", // caminho onde irá ser feita autenticação

		"passthrough" => [ "/api/login", "/api/create" ], // Routes onde nao é necessário autenticação

		"secure" => true, // apenas funciona com https

		"relaxed" => [ "localhost", "slimapp" ], // hosts para obter requests sem https

		"callback" => function ( $request, $response, $arguments ) use ( $container ) {
			// Guarda informação da payload do JWT em $container['jwt'] através de closure
			// Podem ser posteriormente acedidos com $this->jwt->propriedade
			$container["jwt"] = $arguments["decoded"];

		},

		"error" => function ( $request, $response, $arguments ) { // resposta no erro

			// Todo se já expirou verificar se refresh token é valido, se sim, redirect para a route requisitada caso contrário mostrar não autorizado com codigo 401
			$uri               = $request->getUri();
			$info              = 'Unauthorized';
			$accessTokenGerado = false;
			$status = 401; // Unauthorized

			// Verificar se refresh token é valido
			try {
				// Válido entao gerar access token e redireccionar
				// Caso o refresh token nao seja valido automaticamente torna-o desativo na BD e apaga cookies
				if ( Token::gerarAccessToken( Token::getRefreshToken() ) ) {
					$status = 200; // ok
					$accessTokenGerado = true;
				}

			} catch ( \Bioliving\Errors\TokenException $e ) {
				// Em ambiente de desenvolvimento mostra info sobre o erro, caso contrario apenas Unauthorized
				$info = Errors::filtroReturn( function ( $e ) {
					return $e->getMessage();
				}, function () {
					return 'Unauthorized';
				}, $e );
			}

			$data["status"] = $status;
			$data["info"]   = $info;
			if ( $accessTokenGerado ) {
				return $response->withHeader( 'Location', $uri );
			} else {
				return $response
						->withHeader( "Content-Type", "application/json" )
						->write( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
			}
		}
] ) );