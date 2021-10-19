@if (session('seid') && !Auth::guest())
    <style>
        .navbar-brand {
            position: absolute;
            width: 100%;
            margin-left: -12px;
            margin-top: 5px;
            height: 35px;
            background: url("{{ URL::to('/') }}/assets/logo/logo-{{ session('seid') }}.png") center / contain no-repeat;
        }
    </style>
@endif
<div class="side-menu" id="sidebar">
    <nav class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
            <div class="brand-wrapper">
                <button type="button" class="navbar-toggle">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <div class="brand-name-wrapper">
                    <p>
                        <a href="{!! route('forcelogout') !!}">
                            <img class="logo" src="{{ URL::to('/') }}/assets/logo/logo_2.png">
							<span>Tax Calendar</span>
						</a>
					</p>
					<p>
                    @if (session('seidLogo') && !Auth::guest())
                        <div><img class="logEmpresaSidebar" src="{{ URL::to('/') }}/assets/logo/Logo-{{ session('seidLogo') }}.png" ><br><br><br></div>
                    @endif
                    </p>
				</div>
			</div>
        </div>

        <!-- Main Menu -->
        <div class="side-menu-container">
            <ul class="nav navbar-nav">

                @if (Auth::guest())
	                <li class="active"><a href="{{ url('/login') }}"><i class="fa fa-btn fa-sign-in"></i> Login</a></li>
                @else
                    <li class="active"><a href="{{ route('home', 'selecionar_empresa', '1') }}"><i class="fa fa-btn fa-home"></i>Home</a></li>

                    <?php # echo "<pre>" ,print_r(session()->get('seid'));exit; ?>
               		@if (!empty(session()->get('seid')))
                    	@if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('gbravo')  )
                        	<li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#tax-calendar"><i class="fa fa-dot-circle-o"></i>Cockpit</a>
								<div id="tax-calendar" class="panel-collapse collapse">
									<div class="panel-body">
										<ul class="nav navbar-nav">
											<li class="panel panel-default" id="dropdown">
                                                @if (!Auth::user()->hasRole('analyst'))
                                                <li><a href="{{ route('home') }}">Entregas Gerais</a></li>
                                                <li><a href="{{ route('dashboard') }}">Entregas por Obrigação Interno</a></li>

												@if ( Auth::user()->hasRole('admin'))
                                                <li><a href="{{ route('dashboard2') }}">Entregas por Obrigação</a></li>
                                                @endif

                                                <li><a href="{{ route('dashboard_analista') }}">Entregas por UF e Municípios</a></li>
                                                <li><a href="{{ route('consulta_procadm') }}">Consulta Processos Administrativos</a></li>
                                                <!--task-347 desativada temporariamente-->
                                                <!--<li><a href="{{ route('sped_fiscal') }}">Consulta Sped Fiscal</a></li>-->
                                                <li><a href="{{ route('monitorcnd.graficos') }}">Monitor CND</a></li>
                                                @if ( Auth::user()->hasRole('gbravo'))
                                                    <li><a href="{{ route('status_empresas') }}">Status por Empresa</a></li>
                                                @endif
                                                <li><a href="{{ route('about') }}">Performance</a></li>
                                                <li><a href="{{ route('cargas_grafico') }}"> Status das Integrações</a></li>
                                                <li><a href="{{ route('graficos') }}" target="_blank">Visão Geral</a></li>
                                                <li><a href="{{ route('desempenho_entregas') }}" target="_blank">Desempenho das Entregas</a></li>
                                                @endif
                                                <!-- task 416 -->
                                                @if ( Auth::user()->hasRole('admin'))
                                                <li><a href="{{ route('cronogramaatividades.GerarConsultaCalendario') }}">Cronograma Gerencial</a></li>
                                                @endif
                                                <!-- /task 416 -->
                                                <li><a href="{{ route('arquivos.index') }}">Arquivos</a></li>
                                                <li><a href="{{ route('arquivos.downloads') }}">Download Arquivos</a></li>
											</li>
										</ul>
									</div>
								</div>
                            </li>
                    	@endif
						@if ( Auth::user()->hasRole('gcliente'))
                        	<li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#tax-calendar"><i class="fa fa-dot-circle-o"></i>Cockpit</a>
								<div id="tax-calendar" class="panel-collapse collapse">
									<div class="panel-body">
										<ul class="nav navbar-nav">
											<li class="panel panel-default" id="dropdown">
												<li><a href="{{ route('arquivos.index') }}">Arquivos</a></li>
												<li><a href="{{ route('arquivos.downloads') }}">Download Arquivos</a></li>
                                                <li><a href="{{ route('home') }}">Entregas Gerais</a></li>
                                                <li><a href="{{ route('dashboard2') }}">Entregas por Obrigação</a></li>
											</li>
										</ul>
									</div>
								</div>
                            </li>
						@endif

                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#tax-calendar2"><i class="fa fa-btn fa-calendar"></i>Paralegal</a>
								<div id="tax-calendar2" class="panel-collapse collapse">
									<div class="panel-body">
										<ul class="nav navbar-nav">
											<li class="panel panel-default" id="dropdown">
												<a data-toggle="collapse" href="#conta-corrente"><i class="fa fa-usd" aria-hidden="true"></i> Conta Corrente</a>
												<div id="conta-corrente" class="panel-collapse collapse">
													<div class="panel-body">
														<ul class="nav navbar-nav">
															<li><a href="{{ route('movtocontacorrentes.search') }}"> Manipular</a></li>
															<li><a href="{{ route('movtocontacorrentes.import') }}"> Importar</a></li>
															<li><a href="{{ route('movtocontacorrentes.consultagerencial') }}"> Consulta Gerencial</a></li>
														</ul>
													</div>
												</div>
											</li>
											<li class="panel panel-default" id="dropdown">
												<a data-toggle="collapse" href="#processos-administrativos"><i class="fa fa-inbox" aria-hidden="true"></i> Processos Administrativos</a>
												<div id="processos-administrativos" class="panel-collapse collapse">
													<div class="panel-body">
														<ul class="nav navbar-nav">
															<li><a href="{{ route('processosadms.create') }}"> Adicionar</a></li>
															<li><a href="{{ route('processosadms.search') }}"> Consultar</a></li>
															<li><a href="{{ route('processosadms.import') }}"> Importar</a></li>
														</ul>
													</div>
												</div>
											</li>
                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#monitor-cnd"><i class="fa fa-inbox" aria-hidden="true"></i> Monitor de CND</a>
                                                <div id="monitor-cnd" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('monitorcnd.create') }}"> Adicionar</a></li>
                                                            <li><a href="{{ route('monitorcnd.search') }}"> Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>
										</ul>
                                    </div>
								</div>
                            </li>
                        @endif

                        @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('analyst'))
                        <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#integracoes"><i class="fa fa-exchange" aria-hidden="true"></i>Integrações</a>
                                    <div id="integracoes" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <ul class="nav navbar-nav">
                                                <li class="panel panel-default" id="dropdown">
                                                    <li><a href="{{ route('cargas') }}">Cargas</a></li>
                                                </li>
                                        </ul>
                                    </div>
                                </div>

                            </li>
                    @endif
                    @if ( Auth::user()->hasRole('msaf') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('analyst'))
                        <li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#impostos"><i class="fa fa-exchange" aria-hidden="true"></i>Impostos</a>
                            <div id="impostos" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <ul class="nav navbar-nav">
                                        <li class="panel panel-default" id="dropdown">
                                            <li><a href="{{ route('impostos.selecionarguias') }}">Importar Guias</a></li>
                                        </li>
                                        <li class="panel panel-default" id="dropdown">
                                            <li><a href="{{ route('impostos.consultar') }}">Consultar</a></li>
                                        </li>
                                        <li class="panel panel-default" id="dropdown">
                                            <li><a href="{{ route('impostos.conferenciaguias') }}">Conferência</a></li>
                                        </li>
                                        <li class="panel panel-default" id="dropdown">
                                            <li><a href="{{ route('impostos.liberarclientesemzfic') }}">Liberar Cliente sem Zfic</a></li>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('gcliente'))
                    	<li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#pagamentos-icms"><i class="fa fa-money" aria-hidden="true"></i>Pagto ICMS/Patrocínio</a>
							<div id="pagamentos-icms" class="panel-collapse collapse">
								<div class="panel-body">
									<ul class="nav navbar-nav">
										<li class="panel panel-default" id="dropdown">
											<li><a href="{{ route('guiaicms.icms') }}">ICMS/PATROCINIO</a></li>

											@if ( Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
												<li><a href="{{ route('guiaicms.conferencia') }}">Conferência</a></li>
											@endif
											@if ( !Auth::user()->hasRole('gcliente'))
											<li><a href="{{ route('guiaicms.search_criticas') }}">Criticas</a></li>
											@endif
                                            @if ( !Auth::user()->hasRole('gcliente'))
                                                <li><a href="{{ route('guiaicms.cadastrar') }}">Incluir</a></li>
                                                <li><a href="{{ route('guiaicms.listar') }}">Manipular</a></li>
                                                <li><a href="{{ route('codigosap.create') }}">Atualizar código SAP</a></li>
                                                <li><a href="{{ route('centrocustos.create') }}">Atualizar Centro de Custo</a></li>
                                            @endif
										</li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                    	<li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#pagamentos-iss"><i class="fa fa-money" aria-hidden="true"></i>Pagamentos ISS</a>
							<div id="pagamentos-iss" class="panel-collapse collapse">
								<div class="panel-body">
									<ul class="nav navbar-nav">
										<li class="panel panel-default" id="dropdown">
											<li><a href="{{ route('guiaiss.lerGuiaISS') }}">Ler Guia de Pagamento</a></li>
											<li><a href="{{ route('guiaiss.gerarLotePagamento') }}">Gerar Lote de Pagamento</a></li>
											<li><a href="{{ route('guiaiss.conciliacaoMemoriaGuias') }}">Conciliação Memória x Guias</a></li>
										</li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    @endif

                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || (Auth::user()->hasRole('gcliente') && session('Empresa')->cliente_aprova == 'S') )
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#workflow-manager"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Workflow Manager</a>

                                <div id="workflow-manager" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            @if ( Auth::user()->hasRole('gcliente') && session('Empresa')->cliente_aprova == 'S' )
                                                <li><a href="{{ route('entregas.index') }}"><i class="fa fa-btn fa-upload"></i>Entregas</a></li>
                                            @else
                                                <li><a href="{{ route('aprovacao') }}"><i class="fa fa-btn fa-upload"></i>Aprovação</a></li>
                                                <li><a href="{{ route('entregas.index') }}"><i class="fa fa-btn fa-upload"></i>Entregas</a></li>
                                                <li><a href="{{ route('guiaicms.search_criticas_entrega') }}"><i class="fa fa-btn fa-trash"></i>Criticas Entrega</a></li>
<!-- card419 nao possui mais esta opção         <li><a href="{{ route('sped_fiscal.transmitirlistar') }}"><i class="fa fa-btn fa-rss-square"></i>Transmitir Sped Fiscal</a></li>-->
												<li><a href="{{ route('renomear_arquivos') }}"><i class="fa fa-btn fa-upload"></i>Renomear arquivos</a></li>
												<li>
													<a data-toggle="collapse" href="#justificativa"><i class="fa fa-btn fa-table"></i>Justificativas</a>
													<div id="justificativa" class="panel-collapse collapse">
														<div class="panel-body">
															<ul class="nav navbar-nav">
																<li><a href="{{ route('justificativa.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
																<li><a href="{{ route('justificativa.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
															</ul>
														</div>
													</div>
												</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif

                        <!--task-341-->
                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') )

                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#auditoria"><i class="fa fa-paper-plane-o" aria-hidden="true"></i> Auditoria</a>

                             <div id="auditoria" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                                @if ( Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin'))
                                                    <li><a href="{{ route('validador.index') }}"><i class="fa fa-btn fa fa-table"></i>Auditoria ICMS</a></li>
                                                @endif
                                        </ul>
                                    </div>
                                </div>
                            </li>

                        @endif
                        <!--end task-341-->


                        @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#repository"><i class="fa fa-database" aria-hidden="true"></i> Repository</a>
                                <div id="repository" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            <li><a href="{{ route('arquivos.index') }}"><i class="fa fa-btn fa-upload"></i>Arquivos</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif

                        @if (Auth::user()->hasRole('gcliente') || Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                            <li class="panel panel-default" id="dropdown">
                                <a data-toggle="collapse" href="#concil-vendas"><i class="fa fa-database" aria-hidden="true"></i> Conciliação de Vendas</a>
                                <div id="concil-vendas" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            <li><a href="{{ route('conciliacaovendas.index') }}"><i class="fa fa-btn fa-upload"></i>LINX X JDE</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif

					{{-- TODO: verificar esse endif aqui debaixo --}}
					@endif

                        @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                        <li class="panel panel-default" id="dropdown">
                            <a data-toggle="collapse" href="#configuration"><i class="fa fa-cog" aria-hidden="true"></i> Configuration</a>
                                <div id="configuration" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <ul class="nav navbar-nav">
                                            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                                            <li><a href="{{ route('categorias.index') }}">Categorias Fiscais</a></li>
                                            <li><a href="{{ route('tributos.index') }}">Tributos</a></li>
                                            <li><a href="{{ route('regras.index') }}">Regras</a></li>

                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#regrasLote">Regras Envio por Lote</a>
                                                <div id="regrasLote" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('regraslotes.envio_lote') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('regraslotes.lote_consulta') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#usuarios">Usuários</a>
                                                <div id="usuarios" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('usuarios.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('usuarios.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>

                                            <li class="panel panel-default" id="dropdown">
                                                <a data-toggle="collapse" href="#grupoEmpresas">Grupo de Empresas</a>
                                                <div id="grupoEmpresas" class="panel-collapse collapse">
                                                    <div class="panel-body">
                                                        <ul class="nav navbar-nav">
                                                            <li><a href="{{ route('grupoempresas.create') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
                                                            <li><a href="{{ route('grupoempresas') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>

                                            <li><a href="{{ route('atividades.index') }}">Atividades</a></li>
                                            <li><a href="{{ route('guiaicms.Job', 'getType=1') }}">Ler PDF</a></li>
                                            @endif
                                            <li><a href="{{ route('empresas.index') }}">Empresas</a></li>
                                            @if (!empty(session()->get('seid')))
                                                <li><a href="{{ route('estabelecimentos.index') }}">Estabelecimentos</a></li>
                                            @endif
                                            <li><a href="{{ route('municipios.index') }}">Municipios</a></li>
                                            <li><a href="{{ route('feriados') }}">Feriados</a></li>
											<li><a href="{{ route('mensageriaprocadms.create') }}">Mensageria Processo Administrativo</a></li>
											@if ( Auth::user()->hasRole('admin'))
											<li><a href="{{ route('documentacao.subcategoria.listar') }}">Subcategoria Documentos</a></li>
											@endif
                                        </ul>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endif
                    @if ( Auth::user()->hasRole('analyst') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner'))
                    <li class="panel panel-default" id="dropdown">
                    <a data-toggle="collapse" href="#cronograma"><i class="fa fa-clock-o"></i>Cronograma</a>
                        <div id="cronograma" class="panel-collapse collapse">
                            <div class="panel-body">
                            <ul class="nav navbar-nav">
                                <li class="panel panel-default" id="dropdown">
									<li class="panel panel-default" id="dropdown">
										<a data-toggle="collapse" href="#analistas">Analistas</a>
										<div id="analistas" class="panel-collapse collapse">
											<div class="panel-body">
												<ul class="nav navbar-nav">
													<li><a href="{{ route('atividadesanalista.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
													<li><a href="{{ route('atividadesanalista.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
												</ul>
											</div>
										</div>
									</li>
									<li class="panel panel-default" id="dropdown">
										<a data-toggle="collapse" href="#tempoatividade">Tempo de atividade</a>
										<div id="tempoatividade" class="panel-collapse collapse">
											<div class="panel-body">
												<ul class="nav navbar-nav">
													<li><a href="{{ route('tempoatividade.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
													<li><a href="{{ route('tempoatividade.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
												</ul>
											</div>
										</div>
									</li>
                                    <li class="panel panel-default" id="dropdown">
										<a data-toggle="collapse" href="#analistadisponibilidade">Disponibilidade do analista</a>
										<div id="analistadisponibilidade" class="panel-collapse collapse">
											<div class="panel-body">
												<ul class="nav navbar-nav">
													<li><a href="{{ route('analistadisponibilidade.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
													<li><a href="{{ route('analistadisponibilidade.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
												</ul>
											</div>
										</div>
									</li>
                                    <li class="panel panel-default" id="dropdown">
										<a data-toggle="collapse" href="#previsaocarga">Previsão de Carga</a>
										<div id="previsaocarga" class="panel-collapse collapse">
											<div class="panel-body">
												<ul class="nav navbar-nav">
													<li><a href="{{ route('previsaocarga.adicionar') }}"><i class="fa fa-btn fa-file-text-o"></i>Adicionar</a></li>
													<li><a href="{{ route('previsaocarga.index') }}"><i class="fa fa-btn fa-file-text-o"></i>Consultar</a></li>
												</ul>
											</div>
										</div>
									</li>
									<li><a href="{{ route('cronogramaatividades.create') }}">Gerar</a></li>
									<li><a href="{{ route('cronogramaatividades.Loadplanejamento') }}">Planejamento</a></li>
									<li><a href="{{ route('cronogramaatividades.index') }}">Manipular</a></li>
									<li><a href="{{ route('cronogramaatividades.GerarConsulta') }}">Consulta</a></li>
                                    <li><a href="{{ route('cronogramaatividades.GerarConsultaCalendario') }}">Cronograma Gerencial</a></li>
                                    <li><a href="{{ route('cronogramaatividades.GerarchecklistCron') }}">Checklist</a></li>
                                    <!--task-416
                                    <li><a href="{{ route('cronogramaatividades.mensal') }}">Mensal</a></li>
									<li><a href="{{ route('cronogramaatividades.semanal') }}">Semanal</a></li>
                                    <li><a href="{{ route('calendario') }}">Calendário</a></li><--task-347-->

                                </li>
                            </ul>
                            </div>
                        </div>
                    </li>
                    @endif
                @endif
            </ul>
        </div><!-- /.navbar-collapse -->
    </nav>
</div>


<div class="top-header">
    <div class="menu">
        <button type="button" class="navbar-toggle" id="sidebarCollapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
	</div>

	@if (session('seidEmpresa') && !Auth::guest())
	{{-- <div class="nomeEmpresa"><img class="logoEmpresa" src="{{ URL::to('/') }}/assets/logo/Logo-{{ session('seidLogo') }}.png"> {{ session('seidEmpresa') }}</div> --}}
	{{-- <div class="nomeEmpresa"> {{ session('seidEmpresa') }}</div> --}}
	@endif


	@if (!Auth::guest())
    <div class="dropdown navbar-right">
		<img class="userIcon" src="{{ URL::to('/') }}/assets/img/{{ Auth::user()->roles()->first()->name }}-icon.png"
			title="{{ Auth::user()->roles()->first()->display_name }}" /> <spn class="userName"> {{ Auth::user()->name.' ' }} </spn>

        <a id="dLabel" data-target="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <img src="{{ URL::to('/') }}/assets/img/menu.svg">
		</a>
        <ul class="dropdown-menu" aria-labelledby="dLabel">
			<li>
                <a href="{{ URL('/logout') }}">
                    <i class="fa fa-btn fa-sign-out"></i>Logout
                </a>
                <a href="{!! route('forcelogout') !!}">
                    <i class="fa fa-btn fa-sign-out"></i> Plataforma
                </a>
            </li>
        </ul>
    </div>
    @endif
  </div>





 <script type="text/javascript">

        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                 $('#sidebar').toggleClass('active');
                 $('#sidebarCollapse').toggleClass('auto-left');
                 $('#content').toggleClass('auto-left');
            });
        });

        $(function () {
            $('.navbar-toggle').click(function () {
                $('.navbar-nav').toggleClass('slide-in');
                $('.side-body').toggleClass('body-slide-in');
                $('#search').removeClass('in').addClass('collapse').slideUp(200);
            });

        $('#search-trigger').click(function () {
                $('.navbar-nav').removeClass('slide-in');
                $('.side-body').removeClass('body-slide-in');
            });
        });

        $(function() {
            $('#main-menu').smartmenus({
                subMenusSubOffsetX: 1,
                subMenusSubOffsetY: -8
            });
        });
</script>
