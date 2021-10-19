<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agenda Fiscal</title>
<link rel="shortcut icon" href="{{ URL::to('/') }}/faviconbravo.ico" type="image/x-icon">

<!-- BOOTSTRAP CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css">
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/bootstrap-datepicker/1.6.0/css/bootstrap-datepicker.css">
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/bootstrap-modal/2.2.6/css/bootstrap-modal.min.css">
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/bootstrap-switch/3.3.2/css/bootstrap3/bootstrap-switch.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/datatables/1.10.12/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/datatables-tabletools/2.1.5/css/TableTools.min.css">
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/buttons/1.1.2/css/buttons.dataTables.min.css">
<!-- Select2 CSS-->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/select2/4.0.2/css/select2.min.css" />
<!-- Calendar CSS-->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/fullcalendar/2.2.7/fullcalendar.min.css" />
<!-- Highcharts 5.0.9 CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/ajax/libs/highcharts/5.0.9/css/highcharts.css">
<!-- CUSTOM CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/custom.css">
<!-- Smart Menus CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/sm-core-css.css" />
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/sm-clean/sm-clean.css" />


<!-- JQUERY -->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- BOOTSTRAP -->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/bootstrap-datepicker/1.6.0/js/bootstrap-datepicker.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/bootstrap-datepicker/1.6.0/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/bootstrap-modal/2.2.6/js/bootstrap-modal.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/bootstrap-modal/2.2.6/js/bootstrap-modalmanager.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/bootstrap-switch/3.3.2/js/bootstrap-switch.min.js"></script>
<!-- DataTables JS -->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/datatables-tabletools/2.1.5/js/TableTools.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/buttons/1.1.2/js/dataTables.buttons.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/buttons/1.1.2/js/buttons.html5.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/pdfmake/0.1.20/pdfmake.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/pdfmake/0.1.20/vfs_fonts.js"></script>
<!-- select2 JS-->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/select2/4.0.2/js/select2.min.js"></script>
<!-- Highcharts-->

<?php

	if ($_SERVER['REQUEST_URI'] == '/aprovacao') {

		?>

<script src="{{ URL::to('/') }}/assets/js/highcharts/highcharts.js"></script>
<script src="{{ URL::to('/') }}/assets/js/highcharts/highcharts-3d.js"></script>
<script src="{{ URL::to('/') }}/assets/js/highcharts/modules/exporting.js"></script>

<?php
	} else {

?>
<script src="{{ URL::to('/') }}/assets/js/highcharts/highcharts.js"></script> <!-- Lucas adicionou -->
{{-- <script src="{{ URL::to('/') }}/assets/js/ajax/libs/highcharts/4.2.7/highcharts.js"></script> --}}
<script src="{{ URL::to('/') }}/assets/js/highcharts/highcharts-3d.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/highcharts/4.2.7/modules/exporting.js"></script>

<?php } ?>


<script src="{{ URL::to('/') }}/assets/js/ajax/libs/highcharts/4.2.7/highcharts-more.js"></script>
<!-- Masked Input JS -->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<!-- Calendar -->
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/moment.js/2.9.0/moment.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/fullcalendar/2.2.7/fullcalendar.min.js"></script>
<script src="{{ URL::to('/') }}/assets/js/ajax/libs/fullcalendar/2.2.7/lang/pt.js"></script>
<!-- Custom JS -->
<script src="{{ URL::to('/') }}/assets/js/custom.js"></script>
<!-- Smart Menus JS -->
<script src="{{ URL::to('/') }}/assets/js/jquery.smartmenus.min.js"></script>

<!--- format moeda -->
<script src="{{ URL::to('/') }}/assets/js/jquery.maskMoney.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
<!-- Axios -->
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<link href="{{ URL::to('/') }}/assets/css/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="{{ URL::to('/') }}/assets/js/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/js/bootstrap-multiselect.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.13/css/bootstrap-multiselect.css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.8.1/css/bootstrap-select.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.8.1/js/bootstrap-select.js"></script>
</head>