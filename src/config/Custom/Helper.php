<?php
/**
 * Class Helper
 *
 * Tem funcoes utilizadas repetitivamente ao longo do projeto
 *
 * Para utilizar basta meter no topo:
 * use Bioliving\Custom\Helper as H;
 *
 * Depois para invocar uma funcao/metodo basta:
 * H::nomeDaFuncao();
 */

namespace Bioliving\Custom;

class Helper {

	// Utilizada para verificar se um parametro obrigatorio foi enviado no request
	static public function obrigatorio($param){
		return ! is_null( $param ) && ! empty( $param );
	}

	/*
	 * Criar nome de imagem unico, retorna esse nome
	 */
 	static public function gerarIdUnico(){
		return  md5( uniqid( rand(), true ) );
 	}

	/*
	 * Conversão de imagem PNG em JPG
	 */

 	static public function pngToJpg($originalFile, $outputFile, $quality){
		$imagemOriginal = imagecreatefrompng($originalFile);
		$imagemJpg = imagejpeg($imagemOriginal, $outputFile, $quality);
		imagedestroy($imagemOriginal);

		return $imagemJpg;
 	}

 	static public function converterBitsMB($bits){
 		return round($bits  / 1024 / 1024,2);
 	}

	/*
	 * Converte caminhos de recursos locais em urls pois o browser nao aceita mostrar ficheiros locais
	 * Exemplo:
	 *
	 * Converte: file:///C:/xampp/htdocs/lab/public/imagens/avatars/e6ffb82712f49443f3649bb61232f577.jpg
	 * Em: http://localhost/lab/public/imagens/avatars/e6ffb82712f49443f3649bb61232f577.jpg
	 *
	 */

 	static public function obterUrl($tipoImagem,$idImagem){
		$pastaUpload = '/imagens/';

		if($tipoImagem === 'avatar'){
			$pastaUpload .= 'avatars/';
		}
		$url = 'http://' . $_SERVER['SERVER_NAME'] .'/'. $pastaUpload . $idImagem;

		return $url;
 	}

 	/*
 	 *	 Filtrar array mutidimensional: remover indices com valores nulls ou strings vazias ''
 	 */
 	 static public function filtrarArrMulti($arr){
		 $arr = array_filter(array_map(function ($valor1) {
			 return $valor1 = array_filter($valor1, function ($valor2) {
				 return $valor2 !== null && $valor2 !== '';
			 });
		 }, $arr));

		 return $arr;
 	 }

	static public function filtrarArr($arr){
		$arr = array_filter(array_map(function ($valor) {
				if($valor !== null && $valor !== '')
				return $valor;
				else return false;
		}, $arr));

		return $arr;
	}

	/*
	 * Obter url atual para enviar na resposta a proxima pagina
	 * Ex: Converte slimapp/api/pesquisa/eventos em pesquisa/eventos
	 *
	 */

	 static public function nextPageUrl(){
		 // Obter url e apenas obter a query
		 $nextPageUrl = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

		 // Divir por /
		 $nextPageUrl = explode('/',$nextPageUrl);

		 // Remover dois primeiros (Remover o /api/"
		 unset($nextPageUrl[0]); unset($nextPageUrl[1]);

		 // Juntar de novo
		 $nextPageUrl = implode('/',$nextPageUrl);

		 return $nextPageUrl;
	 }

	 /*
	  * Validação de nomes com espaços e caracteres latins
	  */

	// Validar nome,sobrenome
	static public function validarNomes($nome){

		$nomesValidos = false;


		// RegEx para todos os caracteres inclusive latins e outros com minimo de uma letra e maximo de 50 (50 é o que o Facebook utiliza e é limite da base de dados)
		// https://stackoverflow.com/a/5429984/7663060

		$matches  = preg_match ('/^[\p{L}\s]{1,50}$/u', $nome);
		if($matches){
			$nomesValidos = true;
		}

		return (bool) $nomesValidos;
	}



}