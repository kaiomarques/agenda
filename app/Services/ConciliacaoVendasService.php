<?php
namespace App\Services;

use App\Console\Commands\ConciliacaoVendas;
use App\Utils\Collection;
use App\Utils\Folder;
use App\Utils\ProjectConfig;

class ConciliacaoVendasService {

	private $pastaStorage = null;

	/** @var ConciliacaoVendas $command */
	private $command;
	private $glue;

	private static $linhaInicioJDE  = 2;
	private static $linhaInicioLINX = 3;

	/** @var ProjectConfig $projectConfig */
	private $projectConfig;

	private $delimiter;

	function __construct() {
		$this->projectConfig = new ProjectConfig();
		$this->glue          = $this->projectConfig->getGlue();
		$this->pastaStorage  = $this->projectConfig->getPastaStorage();
		$this->delimiter     = ';';
	}

	/** @return int */
	public static function getLinhaInicioJDE() {
		return self::$linhaInicioJDE;
	}

	/** @return int */
	public static function getLinhaInicioLINX() {
		return self::$linhaInicioLINX;
	}

	/**
	 * @param ConciliacaoVendas $command
	 * @return void */
	public function setCommand($command) {
		$this->command = $command;
	}

	/** @return Collection */
	public function getOperacoes() {

		$dataset = [];

		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'S0', 'descricao' => 'SIMPLES FATURAMENTO VENDA ANTECIPADA']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'SO', 'descricao' => 'VENDA E-COMM']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'SQ', 'descricao' => 'VENDA ATACADO']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'SX', 'descricao' => 'VENDA DE ENTREGA FUTURA']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'SY', 'descricao' => 'VENDAS CUPOM FISCAL']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'UD', 'descricao' => 'REM VENDA FORA ESTAB']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'V4', 'descricao' => 'VENDA PROD. CONSIGNADO']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'V8', 'descricao' => 'DEV VENDA              -BRAZIL']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'VM', 'descricao' => 'DEVOLUÇÃO DE VENDAS ECOMMERCE']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'VN', 'descricao' => 'DEVOLUÇÃO DE VENDAS LOJAS']);
		array_push($dataset, ['status' => 1, 'tipo_pedido' => 'VR', 'descricao' => 'REVERSÃO VENDA E-COMM']);

		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'O2', 'descricao' => 'REMESSA CONSUMO X.949']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'O3', 'descricao' => 'FRETE']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'OD', 'descricao' => 'REM CONSERTO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'OF', 'descricao' => 'COMPRA CONSUMO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'OP', 'descricao' => 'ENTRADA IMPORTAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'OS', 'descricao' => 'ENTRADA TRANSFERENCIA']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'O5', 'descricao' => 'ENTRADA SIMBOLICA TECADI']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'O4', 'descricao' => 'ENTRADA SIMBOLICA TECADI']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'S5', 'descricao' => 'REMESSA DOACAO INC RH']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'S6', 'descricao' => 'REM PEÇAS TECADI']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'S9', 'descricao' => 'REMESSA DOACAO SAC']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'SD', 'descricao' => 'REMESSA CONSERTO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'SK', 'descricao' => 'TRANSF. RET. ENTRADA REM. CONSERTO (SAS ENTRE LOJAS)']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'SM', 'descricao' => 'REMESSA DOACAO MARKETING']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'SU', 'descricao' => 'REMESSA SIMB PARA ARMAZ TECADI']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'UA', 'descricao' => 'REMESSA PARA CONSERTO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'UC', 'descricao' => 'TRANSF PRODUTO ENTRE LOJAS']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'V0', 'descricao' => 'ENTRADA REMESSA PARA CONSERTO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'V1', 'descricao' => 'COMPLEMENTAR']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'V2', 'descricao' => 'RETORNO DEMONSTRAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'V9', 'descricao' => 'TRANSF DE PRODUTO SAIDA BV2 BR']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VA', 'descricao' => 'RET CONSERTO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VF', 'descricao' => 'REMESSA CONTA E ORDEM']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VG', 'descricao' => 'REMESSA DEMONSTRAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VH', 'descricao' => 'REMESSA EM DOAÇÃO SACC']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VQ', 'descricao' => 'TROCA CUPOM FISCAL']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VT', 'descricao' => 'RET DEMONSTRAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'VV', 'descricao' => 'RET DOAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'W2', 'descricao' => 'RETORNO ENTRADA REMESSA PARA CONSERTO (SAS)']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'W5', 'descricao' => 'REMESSA PARA DESTRUIÇÃ']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'w8', 'descricao' => 'REVERSÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WA', 'descricao' => 'BONIFICAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WF', 'descricao' => 'CANCELAMENTO DE NOTA CONTABILIZADA PEDIDOS SQ']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WI', 'descricao' => 'RETORNO FISICO DE CONSIGNAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WJ', 'descricao' => 'ENTRADA TRANSFERÊNCIA']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WK', 'descricao' => 'REMESSA INDUSTRIALIZACAO / DEMONSTRAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WL', 'descricao' => 'RET REMESSA INDUSTRIALIZACAO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WN', 'descricao' => 'REVERSÃO DEVOLUÇÃO - ENTRADA']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WQ', 'descricao' => 'REM CONSIGNAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WV', 'descricao' => 'CANC SIMPLES FATUR CONSIGNACAO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WX', 'descricao' => 'RET SIMBÓLICO CONSIG']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'wy', 'descricao' => 'REVERSÃO DE NF INUTILIADA LINX']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'W9', 'descricao' => 'BONIFICAÇÃO FRANQUIA']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'WZ', 'descricao' => 'DEVOLUÇÃO BONIFICAÇÃO']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'O6', 'descricao' => 'NF VARIAÇÃO CAMBIAL']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'OX', 'descricao' => 'REM. CONSERTO - NF CLIENTE']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'SH', 'descricao' => 'REM. BONIFICAÇÃO RETAIL']);
		array_push($dataset, ['status' => 0, 'tipo_pedido' => 'FR', 'descricao' => 'FRANQUIA']);

		return new Collection($dataset);
	}

	public function auto_get($value) {

		$value = utf8_encode((isset($value) ? trim($value) : ''));
		$len   = strlen($value);

		if (strpos($value, '"') !== false) {
			$value = trim($value, '" ');
		}
		else {

			if ($len == 10 && preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = array_shift($aux);
				$mes   = array_shift($aux);
				$ano   = substr(array_shift($aux), 2, 2);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 9 && preg_match('/^[0-9]{1}\/[0-9]{2}\/[0-9]{4}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$mes   = array_shift($aux);
				$ano   = substr(array_shift($aux), 2, 2);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 9 && preg_match('/^[0-9]{2}\/[0-9]{1}\/[0-9]{4}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = array_shift($aux);
				$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$ano   = substr(array_shift($aux), 2, 2);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 8 && preg_match('/^[0-9]{1}\/[0-9]{1}\/[0-9]{4}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$ano   = substr(array_shift($aux), 2, 2);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 7 && preg_match('/^[0-9]{1}\/[0-9]{2}\/[0-9]{2}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$mes   = array_shift($aux);
				$ano   = array_shift($aux);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 7 && preg_match('/^[0-9]{2}\/[0-9]{1}\/[0-9]{2}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = array_shift($aux);
				$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$ano   = array_shift($aux);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else if ($len == 6 && preg_match('/^[0-9]{1}\/[0-9]{1}\/[0-9]{2}/', $value)) {
				$aux   = explode('/', $value);
				$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
				$ano   = array_shift($aux);
				$value = implode('/', [$dia, $mes, $ano]);
			}
			else {

				if (strpos($value, ',') !== false && strpos($value, '.') !== false) { // 1.665,99
					$aux = str_replace(',', '.', str_replace('.', '', $value));
				}
				else {
					$aux = str_replace(',', '.', $value);
				}

				if (is_numeric($aux)) {

					if (strpos($aux, '.') !== false) { // float
						$value = floatval($aux);
					}
					else {

						if (strlen($aux) >= 2 && substr($aux, 0, 1) == '0') { // começa com zero a esquerda
							$value = $aux; // string (cnpj, cpf)
						}
						else {
							$value = intval($aux);
						}

					}

				}

			}

		}

		return $value;
	}

	public function getCodigoFilial($strFilial) {

		$aux = trim($strFilial);

		if (!empty($aux)) {

			if (strpos($aux, ' ') !== false) {
				$aux = explode(' ', $aux);
				return array_shift($aux);
			}

		}

		return $aux;
	}

	/**
	 * @param string $line
	 * @return string */
	public function fixLine($line) {

		if (is_string($line) && $line !== '') {

			$scope = '';
			$aux   = [];

			for ($x = 0, $len = strlen($line); $x < $len; $x++) {

				$c = substr($line, $x, 1);

				if ($scope == 'STRING') {

					if ($c == '"') { // fechou a string
						$scope = '';
					}
					else if ($c != $this->delimiter) {
						array_push($aux, $c);
					}

				}
				else {

					if ($c == '"') {
						$scope = 'STRING';
					}
					else {
						array_push($aux, $c);
					}

				}

			}

			return implode('', $aux);
		}

		return '';
	}

	public function file2collection($filename, $columnStart = 1, $callback = null) {

		$collection = new Collection();
		$handle     = fopen($filename, 'r');
		$count      = 1;

		while (!feof($handle)) {

			$line = trim(fgets($handle));

			if ($count >= $columnStart && !empty($line)) {

				$split = explode($this->delimiter, $this->fixLine($line)); // o linx tem 83 colunas (array de 0 a 82)
				$linha = ['LINE' => $count];

				// TODO: popular apenas as colunas que usarei (evitar overflow) - receber parametro com as colunas que desejo (de entrada e de saida pois são diferentes)

				foreach ($split as $index => $val) {
					$attr = implode('', ['COLUNA', ($index + 1)]);
					$linha[$attr] = $this->auto_get($val);
				}

				$collection->add($linha, $callback);
			}

			$count++;
		}

		fclose($handle);

		return $collection;
	}

	public function getMes($mes) {

		$mes   = intval($mes);
		$meses = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];

		return (isset($meses[$mes]) ? $meses[$mes] : null);
	}

	public function doProcessa($cnpj_preffix, $periodo, $linhaInicioJDE = null, $linhaInicioLINX = null) {

		$command         = $this->command;
		$vars            = [];
		$service         = $this;
		$linhaInicioJDE  = isset($linhaInicioJDE)  ? $linhaInicioJDE  : ConciliacaoVendasService::getLinhaInicioJDE();
		$linhaInicioLINX = isset($linhaInicioLINX) ? $linhaInicioLINX : ConciliacaoVendasService::getLinhaInicioLINX();
		$mes             = substr($periodo, 0, 2);
		$ano             = substr($periodo, 2, 4);
		$mesStr          = $this->getMes($mes);

		/** @var ConciliacaoVendasService $service */

		if (file_exists($this->pastaStorage)) {

			$folder = $this->projectConfig->getPastaEmpresa($cnpj_preffix);
			$error  = null;

			if ($folder !== null) {

				$vars['file_erp'] = implode($this->glue, [$folder->fullpath, 'jde_x_linx', 'ARQUIVOS JDE', "FATURAMENTO_{$mesStr}{$ano}.csv"]);
				$vars['file_pdv'] = implode($this->glue, [$folder->fullpath, 'jde_x_linx', 'ARQUIVOS LINX', "{$mes}.{$ano}.csv"]);

				if (!file_exists($vars['file_pdv'])) {
					$error = sprintf('O seguinte arquivo LINX (%s) não existe', $vars['file_pdv']);
				}

				if (!file_exists($vars['file_erp'])) {
					$error = sprintf('O seguinte arquivo JDE (%s) não existe', $vars['file_erp']);
				}

				if ($error === null) {
/*
				 PDV (LINX) => [
					 'COLUNA2' => 'filial',
					 'COLUNA3' => 'dt_venda',
					 'COLUNA6' => 'nr_nota',
					 'COLUNA33' => 'vl_liquido',
					 'COLUNA76' => 'vv3-vloricms',
					 'COLUNA78' => 'vv3-valoripi',
					 'COLUNA80' => 'vv3-valorpis',
					 'COLUNA82' => 'vv3-valorcofins',
				 ];
				 ERP (JDE) => [
					 'COLUNA1' => 'nr_nota',
					 'COLUNA2' => 'serie',
					 'COLUNA7' => 'Tp Pedido/Operação',
					 'COLUNA20' => 'filial',
					 'COLUNA29' => 'preco_total',
					 'COLUNA45' => 'CFOP',
					 'COLUNA56' => 'vl_icms',
					 'COLUNA64' => 'vl_ipi',
					 'COLUNA69' => 'vl_pis',
					 'COLUNA71' => 'vl_cofins',
					 'COLUNA81' => 'dt_emissao',
				 ];
*/
					$operacoes     = $this->getOperacoes()->filter(['status' => 1])->getColumn('tipo_pedido');
					$data          = ((object) [
						'notas'      => [],
						'notas_jde'  => [],
						'tuplas_jde' => new Collection(),
						'criticas'   => [],
						'count'      => 0
					]);
					$this->file2collection($vars['file_erp'], $linhaInicioJDE, function ($linha, Collection $collection) use ($data, $service, $operacoes) {

						$nr_nota     = $collection->getTupleValue($linha, 'COLUNA1');
						$serie       = $collection->getTupleValue($linha, 'COLUNA2');
						$tp_pedido   = $collection->getTupleValue($linha, 'COLUNA7');
						$filial      = $collection->getTupleValue($linha, 'COLUNA20');
						$preco_total = $collection->getTupleNumber($linha, 'COLUNA29');
						$CFOP        = $collection->getTupleValue($linha, 'COLUNA45');
						$vl_icms     = $collection->getTupleNumber($linha, 'COLUNA56');
						$vl_ipi      = $collection->getTupleNumber($linha, 'COLUNA64');
						$vl_pis      = $collection->getTupleNumber($linha, 'COLUNA69');
						$vl_cofins   = $collection->getTupleNumber($linha, 'COLUNA71');
						$dt_venda    = $collection->getTupleValue($linha, 'COLUNA81');

						if ($filial !== null && $dt_venda !== null && $nr_nota !== null) {

							if ($tp_pedido !== null && in_array($tp_pedido, $operacoes)) {

								if (!isset($data->notas_jde[$filial])) {
									$data->notas_jde[$filial] = [];
								}

								if (!isset($data->notas_jde[$filial][$dt_venda])) {
									$data->notas_jde[$filial][$dt_venda] = [];
								}

								if (!isset($data->notas_jde[$filial][$dt_venda][$nr_nota])) {
									$data->notas_jde[$filial][$dt_venda][$nr_nota] = [];
								}

								$tupla_jde = [
									'FILIAL'      => $filial,
									'DT_VENDA'    => $dt_venda,
									'NR_NOTA'     => $nr_nota,
									'SERIE'       => $serie,
									'TP_PEDIDO'   => $tp_pedido,
									'PRECO_TOTAL' => $preco_total,
									'CFOP'        => $CFOP,
									'VL_ICMS'     => $vl_icms,
									'VL_IPI'      => $vl_ipi,
									'VL_PIS'      => $vl_pis,
									'VL_COFINS'   => $vl_cofins
								];

								array_push($data->notas_jde[$filial][$dt_venda][$nr_nota], $tupla_jde);
								$data->tuplas_jde->add($tupla_jde);
							}

						}

						return false;
					});

					$header   = ['', 'Filial', 'Data', 'Nota', 'Valor', 'ICMS', 'IPI', 'PIS', 'COFINS', 'Critica'];
					$header2  = ['', 'Filial', 'Data', 'Nota', 'Operação', 'Valor', 'ICMS', 'IPI', 'PIS', 'COFINS', 'Critica'];
					$obs      = [
//						'As colunas do arquivo devem ser separadas por uma tabulação',
						sprintf('Os registros do LINX são verificados apenas nas operações: %s', implode(', ', $operacoes)),
						"Os valores de conciliação foram formatados no formato (1.665,98) para verificação"
					];

					echo '<b>Observações:</b><br />';
					echo '<span style="color:red; font-weight:bold;">', implode('<br />', $obs), '</span>';

					printf('
					<div class="panel with-nav-tabs panel-primary">
						<div class="panel-heading">
							<ul class="nav nav-tabs">
								<li class="active"><a href="#tab1primary" data-toggle="tab">LINX => JDE</a></li>
								<li><a href="#tab2primary" data-toggle="tab">JDE => LINX</a></li>
							</ul>
						</div>
						<div class="panel-body">
							<div class="tab-content">
								<div id="tab1primary" class="tab-pane fade in active">
									<div class="table-default table-responsive">
										<table class="table table-hover table-sm display" id="grid-criticas">
											<thead>%s</thead>
											<tbody>', (!empty($header) ? sprintf('<tr class="top-table thead-light"><th>%s</th></tr>', implode('</th><th>', $header)) : ''));

					$this->file2collection($vars['file_pdv'], $linhaInicioLINX, function ($linha, Collection $collection) use ($data, $service) {

						$filial     = $collection->getTupleValue($linha, 'COLUNA2');
						$dt_venda   = $collection->getTupleValue($linha, 'COLUNA3');
						$nr_nota    = $collection->getTupleValue($linha, 'COLUNA6');
						$vl_liquido = $collection->getTupleNumber($linha, 'COLUNA33');
						$vl_icms    = $collection->getTupleNumber($linha, 'COLUNA76');
						$vl_ipi     = $collection->getTupleNumber($linha, 'COLUNA78');
						$vl_pis     = $collection->getTupleNumber($linha, 'COLUNA80');
						$vl_cofins  = $collection->getTupleNumber($linha, 'COLUNA82');
						$codFilial  = $service->getCodigoFilial($filial);
						$line       = $collection->getTupleValue($linha, 'LINE');
						$critica    = [];
						$tupla_linx = [
							'FILIAL'     => $codFilial,
							'DT_VENDA'   => $dt_venda,
							'NR_NOTA'    => $nr_nota,
							'VL_LIQUIDO' => $vl_liquido,
							'VL_ICMS'    => $vl_icms,
							'VL_IPI'     => $vl_ipi,
							'VL_PIS'     => $vl_pis,
							'VL_COFINS'  => $vl_cofins,
							'LINE'       => $line
						];

						if ($filial !== null && $dt_venda !== null && $nr_nota !== null) {

							if (!isset($data->notas[$codFilial])) {
								$data->notas[$codFilial] = [];
							}

							if (!isset($data->notas[$codFilial][$dt_venda])) {
								$data->notas[$codFilial][$dt_venda] = [];
							}

							if (!isset($data->notas[$codFilial][$dt_venda][$nr_nota])) {
								$data->notas[$codFilial][$dt_venda][$nr_nota] = [];
							}

							array_push($data->notas[$codFilial][$dt_venda][$nr_nota], $tupla_linx);

							if (isset($data->notas_jde[$codFilial])) {

								if (isset($data->notas_jde[$codFilial][$dt_venda])) {

									if (isset($data->notas_jde[$codFilial][$dt_venda][$nr_nota])) {

										$qt_notas = count($data->notas_jde[$codFilial][$dt_venda][$nr_nota]);

										if ($qt_notas > 0) {

											if ($qt_notas == 1) {
												$tupla_jde = $data->notas_jde[$codFilial][$dt_venda][$nr_nota][0];
												$critica   = $service->getCriticas($tupla_jde, $tupla_linx);
											}
											else { // notas duplicadas

												$notas = $data->notas_jde[$codFilial][$dt_venda][$nr_nota];
												$found = null;

												foreach ($notas as $tupla_jde) {

													if (($tupla_jde['PRECO_TOTAL'] == $tupla_linx['VL_LIQUIDO']) &&
														($tupla_jde['VL_ICMS']     == $tupla_linx['VL_ICMS']) &&
														($tupla_jde['VL_IPI']      == $tupla_linx['VL_IPI']) &&
														($tupla_jde['VL_PIS']      == $tupla_linx['VL_PIS']) &&
														($tupla_jde['VL_COFINS']   == $tupla_linx['VL_COFINS'])) {
														$found = $tupla_jde;
														break;
													}

												}

												if ($found != null) {
													$critica = $service->getCriticas($found, $tupla_linx);
												}
												else {
													$critica = $service->getGridColumns($notas, $tupla_linx, 'Notas duplicadas no JDE');
												}

											}

										}
										else {
											$critica = $service->getGridColumns(null, $tupla_linx, 'Nenhuma nota encontrada no JDE');
										}

									}
									else {
										$critica = $service->getGridColumns(null, $tupla_linx, 'Nota não localizada no JDE');
									}

								}
								else {
									$critica = $service->getGridColumns(null, $tupla_linx, 'Data da venda não localizada no JDE');
								}

							}
							else {
								$critica = $service->getGridColumns(null, $tupla_linx, 'Filial não localizada no JDE');
							}

						}
						else {
							$critica = ['', '', '', '', '', '', '', '', 'Filial, data e nota invalidos'];
						}

						if (!empty($critica)) {
							$data->count++;
							printf('<tr><td>%s</td><td nowrap>%s</td></tr>', $data->count, implode('</td><td>', $critica));
						}

						return false;
					});

					printf('
											</tbody>
										</table>
									</div>
								</div>
								<div id="tab2primary" class="tab-pane fade">
									<div class="table-default table-responsive">
										<table class="table table-hover table-sm display" id="grid-criticas2">
											<thead>%s</thead>
											<tbody>', (!empty($header2) ? sprintf('<tr class="top-table thead-light"><th>%s</th></tr>', implode('</th><th>', $header2)) : ''));

					$data->count = 0;
					$data->tuplas_jde->each(function ($tupla_jde, $index, Collection $collection) use ($data, $service) {

						$who       = 'JDE';
						$codFilial = $tupla_jde['FILIAL'];
						$dt_venda  = $tupla_jde['DT_VENDA'];
						$nr_nota   = $tupla_jde['NR_NOTA'];

						if (isset($data->notas[$codFilial])) {

							if (isset($data->notas[$codFilial][$dt_venda])) {

								if (isset($data->notas[$codFilial][$dt_venda][$nr_nota])) {

									$qt_notas = count($data->notas[$codFilial][$dt_venda][$nr_nota]);

									if ($qt_notas > 0) {

										if ($qt_notas == 1) {
											$tupla_linx = $data->notas[$codFilial][$dt_venda][$nr_nota][0];
											$critica    = $service->getCriticas($tupla_jde, $tupla_linx, $who);
										}
										else { // notas duplicadas

											$notas = $data->notas[$codFilial][$dt_venda][$nr_nota];
											$found = null;

											foreach ($notas as $tupla_linx) {

												if (($tupla_jde['PRECO_TOTAL'] == $tupla_linx['VL_LIQUIDO']) &&
													($tupla_jde['VL_ICMS']     == $tupla_linx['VL_ICMS']) &&
													($tupla_jde['VL_IPI']      == $tupla_linx['VL_IPI']) &&
													($tupla_jde['VL_PIS']      == $tupla_linx['VL_PIS']) &&
													($tupla_jde['VL_COFINS']   == $tupla_linx['VL_COFINS'])) {
														$found = $tupla_linx;
														break;
													}

											}

											if ($found != null) {
												$critica = $service->getCriticas($tupla_jde, $found, $who);
											}
											else {
												$critica = $service->getGridColumns($tupla_jde, $notas, 'Notas duplicadas no LINX', $who);
											}

										}

									}
									else {
										$critica = $service->getGridColumns(null, $tupla_linx, 'Nenhuma nota encontrada no LINX', $who);
									}

								}
								else {
									$critica = $service->getGridColumns($tupla_jde, null, 'Nota não localizada no LINX', $who);
								}

							}
							else {
								$critica = $service->getGridColumns($tupla_jde, null, 'Data da venda não localizada no LINX', $who);
							}

						}
						else {
							$critica = $service->getGridColumns($tupla_jde, null, 'Filial não localizada no LINX', $who);
						}

						if (!empty($critica)) {
							$data->count++;
							printf('<tr><td>%s</td><td nowrap>%s</td></tr>', $data->count, implode('</td><td>', $critica));
						}

						return false;
					});

					echo '
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
';
				}
				else {
					$command->info($error);
				}

			}
			else {
				$command->info(sprintf('A pasta da empresa (%s) não existe em %s', $cnpj_preffix, $this->pastaStorage));
			}

		}
		else {
			$command->info(sprintf('Caminho (%s) não existe.', $this->pastaStorage));
		}

	}

	public function getGridColumns($tupla_jde, $tupla_linx, $msg_critica, $who = null) {

		$who = isset($who) ? $who : 'LINX';

		if ($who == 'JDE') {
			return [
				$tupla_jde['FILIAL'],
				$tupla_jde['DT_VENDA'],
				$tupla_jde['NR_NOTA'],
				$tupla_jde['TP_PEDIDO'],
				$tupla_jde['PRECO_TOTAL'],
				$tupla_jde['VL_ICMS'],
				$tupla_jde['VL_IPI'],
				$tupla_jde['VL_PIS'],
				$tupla_jde['VL_COFINS'],
				$msg_critica
			];
		}

		return [
			$tupla_linx['FILIAL'],
			$tupla_linx['DT_VENDA'],
			$tupla_linx['NR_NOTA'],
			$tupla_linx['VL_LIQUIDO'],
			$tupla_linx['VL_ICMS'],
			$tupla_linx['VL_IPI'],
			$tupla_linx['VL_PIS'],
			$tupla_linx['VL_COFINS'],
			$msg_critica
		];
	}

	 /** @return string[] */
	public function getCriticas($tupla_jde, $tupla_linx, $who = null) {

		$grid = [];

		if ($tupla_jde['PRECO_TOTAL'] != $tupla_linx['VL_LIQUIDO']) {
			array_push($grid, sprintf('Valor líquido está divergente. No LINX: %s e no JDE: %s.', $tupla_linx['VL_LIQUIDO'], $tupla_jde['PRECO_TOTAL']));
		}

		if ($tupla_jde['VL_ICMS'] != $tupla_linx['VL_ICMS']) {
			array_push($grid, sprintf('Valor ICMS está divergente. No LINX: %s e no JDE: %s.', $tupla_linx['VL_ICMS'], $tupla_jde['VL_ICMS']));
		}

		if ($tupla_jde['VL_IPI'] != $tupla_linx['VL_IPI']) {
			array_push($grid, sprintf('Valor IPI está divergente. No LINX: %s e no JDE: %s.', $tupla_linx['VL_IPI'], $tupla_jde['VL_IPI']));
		}

		if ($tupla_jde['VL_PIS'] != $tupla_linx['VL_PIS']) {
			array_push($grid, sprintf('Valor PIS está divergente. No LINX: %s e no JDE: %s.', $tupla_linx['VL_PIS'], $tupla_jde['VL_PIS']));
		}

		if ($tupla_jde['VL_COFINS'] != $tupla_linx['VL_COFINS']) {
			array_push($grid, sprintf('Valor COFINS está divergente. No LINX: %s e no JDE: %s.', $tupla_linx['VL_COFINS'], $tupla_jde['VL_COFINS']));
		}

		if (!empty($grid)) {
			$grid = $this->getGridColumns($tupla_jde, $tupla_linx, implode('<br />', $grid), $who);
		}

		return $grid;
	}

}
