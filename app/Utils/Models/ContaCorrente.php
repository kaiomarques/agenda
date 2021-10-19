<?php
namespace App\Utils\Models;

use Exception;

use App\Utils\Model;
use App\Utils\Util;
use App\Utils\Variavel;

/**
 * @property int $estabelecimento_id
 * @property int $status_id
 * @property int $usuario_inclusao
 * @property int $usuario_alteracao
 * @property string $periodo
 * @property double $valor_debito
 * @property string $data_consulta
 * @property string $processo
 * @property string $arquivo
 * @property string $data_inclusao_reg
 * @property string $data_alteracao_reg
 * @property int $motivo_id
 * @property int $risco_id
 * @property string $acao
 * @property string $responsavel_bravo
 * @property string $responsavel_cliente
 * @property string $observacao
 * @property string $ambito
 * @property string $obrigacao
 * @property string $resumo_pendencia
 * @property int $resp_financeiro_id
 * @property int $resp_acompanhar_id
 * @property int $qtde_nf
 * @property string $valor_principal
 * @property string $juros
 * @property string $prazo
 * @property string $documento
 * */
class ContaCorrente extends Model {

	protected $table    = 'contacorrente';
	protected $fillable = [
		'estabelecimento_id'  => 'ID',
		'status_id'           => 'ID',
		'usuario_inclusao'    => 'ID',
		'usuario_alteracao'   => 'ID',
		'periodo'             => 'PERIODO',
		'valor_debito'        => 'MOEDA',
		'data_consulta'       => 'DATE',
		'processo'            => 'STRING',
		'arquivo'             => 'STRING',
		'data_inclusao_reg'   => 'DATE',
		'data_alteracao_reg'  => 'DATE',
		'motivo_id'           => 'ID',
		'risco_id'            => 'ID',
		'acao'                => 'STRING',
		'responsavel_bravo'   => 'STRING',
		'responsavel_cliente' => 'STRING',
		'observacao'          => 'STRING',
		'ambito'              => 'STRING',
		'obrigacao'           => 'STRING',
		'resumo_pendencia'    => 'STRING',
		'resp_financeiro_id'  => 'ID',
		'resp_acompanhar_id'  => 'ID',
		'qtde_nf'             => 'INTEGER',
		'valor_principal'     => 'MOEDA',
		'juros'               => 'MOEDA',
		'prazo'               => 'DATE',
		'documento'           => 'STRING'
	];
	protected static $documento_padrao  = '046';
	protected static $observacao_padrao = '993-VL.TOTAL DA GUIA %s';

	// ============================================================================================

	/** @return Variavel[] */
	public function checkRules() {

		$vars = [];

		$vars['estabelecimento_id'] = Variavel::getId($this->estabelecimento_id, 'estabelecimentos');
		$vars['status_id']          = Variavel::getId($this->status_id, 'statusprocadms');
		$vars['motivo_id']          = Variavel::getId($this->motivo_id, 'motivocontacorrente');
		$vars['risco_id']           = Variavel::getId($this->risco_id, 'riscocontacorrente');
		$vars['ambito']             = Variavel::getAmbito($this->ambito);
		$vars['obrigacao']          = Variavel::getString($this->obrigacao, 250, 1);
		$vars['resumo_pendencia']   = Variavel::getString($this->resumo_pendencia, 2000, 1);
		$vars['resp_financeiro_id'] = Variavel::getResponsavel($this->resp_financeiro_id);
		$vars['resp_acompanhar_id'] = Variavel::getResponsavel($this->resp_acompanhar_id);
		$vars['responsavel_bravo']  = Variavel::getString($this->responsavel_bravo, 100, 2);
		$vars['prazo']              = Variavel::getDate($this->prazo);
		$vars['acao']               = Variavel::getString($this->acao);
		$vars['periodo']            = Variavel::getPeriodo($this->periodo);
		$vars['data_consulta']      = Variavel::getDate($this->data_consulta);
		$vars['observacao']         = Variavel::getString($this->observacao, 2000, 0);

		// regras no card 484

		$validarProcesso = false;
		$validarQtdeNf   = false;
		$validarValores  = false;
		$validarJuros    = false;

		$this->valor_principal = ($this->valor_principal === null || $this->valor_principal === '' ? 0 : $this->valor_principal);
		$this->valor_debito    = ($this->valor_debito    === null || $this->valor_debito    === '' ? 0 : $this->valor_debito);
		$this->qtde_nf         = ($this->qtde_nf         === null || $this->qtde_nf         === '' ? 0 : $this->qtde_nf);
		$this->juros           = ($this->juros           === null || $this->juros           === '' ? 0 : $this->juros);

		if ($vars['motivo_id']->isTrue()) {

			if ($vars['motivo_id']->value() == '11') {
				$validarQtdeNf = true;
			}
			else {

				if ($vars['motivo_id']->value() == '15' || $vars['motivo_id']->value() == '16') {
					$validarProcesso = true;
				}

				if ($vars['motivo_id']->value() != '17') {
					$validarValores  = true;
				}

			}

		}

		if ($vars['periodo']->isTrue()) {

			$dtHoje    = date('d/m/Y');
			$dtPeriodo = $vars['periodo']->toDate();

			if ($this->date_compare($dtPeriodo, $dtHoje) == 1) {
				$validarJuros = true;
			}

		}

		$vars['processo']        = Variavel::getString($this->processo, 50, ($validarProcesso ? 1 : 0));
		$vars['qtde_nf']         = Variavel::getInt($this->qtde_nf, 100, ($validarQtdeNf ? 1 : 0));
		$vars['valor_principal'] = Variavel::getMoeda($this->valor_principal, $validarValores);
		$vars['valor_debito']    = Variavel::getMoeda($this->valor_debito, $validarValores);
		$vars['juros']           = Variavel::getMoeda($this->juros, $validarJuros);

		// tenho que ordenar os campos iguais ao grid da importação

		$novo    = [];
		$orderby = [
			'estabelecimento_id',
			'status_id',
			'motivo_id',
			'risco_id',
			'ambito',
			'obrigacao',
			'resumo_pendencia',
			'resp_financeiro_id',
			'resp_acompanhar_id',
			'responsavel_bravo',
			'qtde_nf',
			'juros',
			'prazo',
			'acao',
			'processo',
			'periodo',
			'data_consulta',
			'valor_principal',
			'valor_debito',
			'observacao'
		];

		foreach ($orderby as $column) {
			$novo[$column] = $vars[$column];
		}

		unset($vars);

		return $novo;
	}

	/**
	 * @desc compara 2 datas ($date1 e $date2 like this 'dd/mm/aaaa')
	 * @param string $date1
	 * @param string $date2
	 * @return int */
	public function date_compare($date1, $date2) {

		$split1 = explode('/', $date1);
		$split2 = explode('/', $date2);
		$aux1   = intval(implode('', [$split1[2], $split1[1], $split1[0]]));
		$aux2   = intval(implode('', [$split2[2], $split2[1], $split2[0]]));

		if ($aux1 != $aux2) {

			$resp = ($aux1 < $aux2 ? 1 : 2);

			if ($resp == 1) {

				if ($split1[2] == $split2[2] && $split1[1] == $split2[1]) { // se mes e ano forem iguais
					return 0;
				}

			}

			return $resp;
		}

		return 0;
	}

	/**
	 * @throws \Exception
	 * @return ContaCorrente */
	public function validate() {

		$error = [];

		if ($this->motivo_id === null) {
			array_push($error, sprintf('campo motivo_id não setado.'));
		}

		if ($this->documento === null) {
			array_push($error, sprintf('campo documento não setado.'));
		}

		if ($this->estabelecimento_id === null) {
			array_push($error, sprintf('campo estabelecimento_id não setado'));
		}

		if ($this->periodo === null) {
			array_push($error, sprintf('campo periodo não setado'));
		}

		if ($this->valor_debito === null) {
			array_push($error, sprintf('campo valor_debito não setado'));
		}

		if ($this->observacao === null) {
			array_push($error, sprintf('campo observacao não setado'));
		}

		if (!empty($error)) {
			throw new Exception(sprintf('%s::%s', __METHOD__, implode('<br />', $error)));
		}

		return $this;
	}

	/**
	 * @throws Exception
	 * @return bool */
	public function podeGravar() {

		$this->validate();

		$gravar = false;

		if (ContaCorrente::getDocumentos($this->documento) != null) {

			if ($this->documento == 'GIA') {
				$this->valor_debito = 0;
				$gravar = (substr($this->observacao, 0, 4) == '102*'); // NAO APRESENTOU GIA
			}
			else {

				if ($this->motivo_id != '17') {
					$gravar = ($this->valor_debito != 0);
				}
				else {
					$gravar = true;
				}

			}

		}
		else {
			throw new Exception(sprintf('Documento (%s) inválido. Válidos são: (%s)', $this->documento, implode('|', ContaCorrente::getDocumentos())));
		}

		return $gravar;
	}

	/**
	 * @desc verifica se existe algum registro no BD filtrando com alguns campos (retorna um novo Model ContaCorrente)
	 * @throws Exception
	 * @return ContaCorrente */
	public function existe() {

		$this->validate();

		$novo = new ContaCorrente();
		$novo->setBy([
			'documento'          => $this->documento,
			'estabelecimento_id' => $this->estabelecimento_id,
			'periodo'            => $this->periodo,
			'valor_debito'       => $this->valor_debito
		]);

		if ($novo->exists()) {

			$oid = sprintf('%s', $this->getIdObservacao());
			$tam = strlen($oid);

			if (substr($this->observacao, 0, $tam) != $oid) {
				$novo->unsetVars();
			}

		}

		return $novo;
	}

	/** @return int */
	public function getIdObservacao() {

		$this->validate();

		$id      = [];
		$numbers = '1234567890';

		if ($this->observacao !== '') {

			for ($x = 0, $len = strlen($this->observacao); $x < $len; $x++) {

				$c = substr($this->observacao, $x, 1);

				if (strpos($numbers, $c) !== false) {
					array_push($id, $c);
				}
				else {
					break;
				}

			}

		}

		return (!empty($id) ? intval(implode('', $id)) : -1);
	}

	// ============================================================================================

	/** @return array */
	public static function getCamposValidacao() {
		return ['documento', 'estabelecimento_id', 'motivo_id', 'periodo', 'valor_debito', 'observacao'];
	}

	/** @return array */
	public static function getLayoutArquivo() {
		return [
			'ESTABELECIMENTO'            => '13.574.594/0001-96',
			'STATUS'                     => '3 - PENDENTE',
			'MOTIVO'                     => '1 - Livro eletrônico',
			'RISCO'                      => '1 - Auto',
			'Âmbito'                     => 'Federal',
			'Obrigação'                  => '-',
			'Resumoda Pendência'         => '-',
			'Responsável Financeiro'     => 'Cliente',
			'Responsável Acompanhamento' => 'Bravo',
			'responsavel_bravo'          => 'Geise',
			'Qtde Notas Fiscais'         => '1',
			'Juros'                      => '1,20',
			'Prazo'                      => '31/05/2021',
			'ACAO'                       => '-',
			'PROCESSO'                   => '993-VL.TOTAL DA GUIA 6.104,54',
			'periodo'                    => '04/2021',
			'DATA CONSULTA'              => '01/04/2021',
			'Valor Principal'            => '1.665,98',
			'valor_debito'               => '1.664,58',
			'observacao'                 => '-'
		];
	}

	public static function getAmbitos($ambito = null) {

		$num   = func_num_args();
		$array = Util::addKeys(['Federal', 'Estadual', 'Municipal']);

		if ($num > 0) {
			return isset($array[$ambito]) ? $array[$ambito] : null;
		}

		return $array;
	}

	public static function getDocumentos($documento = null) {

		$num   = func_num_args();
		$array = Util::addKeys(['GIA', '046']);

		if ($num > 0) {
			return isset($array[$documento]) ? $array[$documento] : null;
		}

		return $array;
	}

	/** @return string */
	public static function getPadraoDocumento() {
		return self::$documento_padrao;
	}

	/**
	 * @desc concatena $suffix no final da observação (se não for string vazia)
	 * @param string $suffix
	 * @return string */
	public static function getPadraoObservacao($suffix = null) {

		if (!empty($suffix)) {
			return sprintf('%s (%s)', self::$observacao_padrao, $suffix);
		}

		return self::$observacao_padrao;
	}

}
