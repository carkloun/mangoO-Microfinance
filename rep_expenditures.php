<!DOCTYPE HTML>
<?PHP
	include 'functions.php';
	check_logon();
	check_report();
	connect();
	
	//Variables $year and $month provide the pre-set values for input fields
	$year = date("Y",time()); 
	$month = date("m",time());
?>
<html>
	<?PHP htmlHead('Expenditures Report',1) ?>	
	<!-- MENU HEADER & TABS -->
		<?PHP 
		include 'menu_header.php';
		menu_Tabs(5);
		?>
		
		<!-- MENU MAIN -->
		<div id="menu_main">
			<a href="rep_incomes.php">Incomes Report</a>
			<a href="rep_expenditures.php" id="item_selected">Expenditures Report</a>
			<a href="rep_loans.php">Loans Report</a>
			<a href="rep_capital.php">Capital Report</a>
			<a href="rep_monthly.php">Monthly Report</a>
			<a href="rep_annual.php">Annual Report</a>
		</div>
		
		<!-- MENU: Selection Bar -->
		<div id="menu_selection">
			<form action="rep_expenditures.php" method="post">
				<input type="number" min="2014" max="2214" name="rep_year" style="width:100px;" value="<?PHP if ($month == 01) echo $year-1; else echo $year; ?>" placeholder="Give Year"></input>
				<select name="rep_month" style="height:24px">
					<option value="01" <?PHP if ($month == 02) echo 'selected="selected"' ?> >January</option>
					<option value="02" <?PHP if ($month == 03) echo 'selected="selected"' ?> >February</option>
					<option value="03" <?PHP if ($month == 04) echo 'selected="selected"' ?> >March</option>
					<option value="04" <?PHP if ($month == 05) echo 'selected="selected"' ?> >April</option>
					<option value="05" <?PHP if ($month == 06) echo 'selected="selected"' ?> >May</option>
					<option value="06" <?PHP if ($month == 07) echo 'selected="selected"' ?> >June</option>
					<option value="07" <?PHP if ($month == 08) echo 'selected="selected"' ?> >July</option>
					<option value="08" <?PHP if ($month == 09) echo 'selected="selected"' ?> >August</option>
					<option value="09" <?PHP if ($month == 10) echo 'selected="selected"' ?> >September</option>
					<option value="10" <?PHP if ($month == 11) echo 'selected="selected"' ?> >October</option>
					<option value="11" <?PHP if ($month == 12) echo 'selected="selected"' ?> >November</option>
					<option value="12" <?PHP if ($month == 01) echo 'selected="selected"' ?> >December</option>
				</select>
				<select name="rep_form" style="height:24px;">
					<option value="d" selected="selected">Detailed Rep.</option>
					<option value="a">Aggregated Rep.</option>
				</select>
				<input type="submit" name="select" value="Select Report" />
			</form>
		</div>
	
		<?PHP
		if(isset($_POST['select'])){	
			//Sanitize user input
			$rep_month = sanitize($_POST['rep_month']);
			$rep_year = sanitize($_POST['rep_year']);
			
			//Calculate UNIX TIMESTAMP for first and last day of selected month
			$firstDay = mktime(0, 0, 0, $rep_month, 1, $rep_year);
			$lastDay = mktime(0, 0, 0, ($rep_month+1), 0, $rep_year);
			
			//Make array for exporting data
			$_SESSION['rep_export'] = array();
			$_SESSION['rep_exp_title'] = $rep_year.'-'.$rep_month.'_expenditures_'.$_POST['rep_form'];
			
			/*** CASE 1: Aggregated Report ***/
			if ($_POST['rep_form'] == 'a'){
				$sql_expendit = "SELECT * FROM expenditures WHERE exp_date BETWEEN $firstDay AND $lastDay ORDER BY exp_date";
				$query_expendit = mysql_query($sql_expendit);
				if (!$query_expendit) die ('SELECT failed: '.mysql_error());
				
				$sql_exptype = "SELECT * FROM exptype";
				$query_exptype = mysql_query($sql_exptype);
				if (!$query_exptype) die ('SELECT failed: '.mysql_error());
				?>
				
				<!-- Export Button -->					
				<form class="export" action="rep_export.php" method="post">
					<input type="submit" name="export_rep" value="Export Report" />
				</form>
				
				<!-- TABLE: Results -->
				<table id="tb_table" style="width:50%">
					<colspan>
						<col width="50%">
						<col width="50%">
					</colspan>
					<tr>
						<th class="title" colspan="2">Aggregated Expenditures for <?PHP echo $rep_month.'/'.$rep_year ?></th>
					</tr>
					<tr>
						<th>Type</th>
						<th>Amount</th>
					</tr>
					<?PHP
					
					$exptype = array();
					while($row_exptype = mysql_fetch_assoc($query_exptype)){
						$exptype[] = $row_exptype;
					}
					
					$expendit = array();
					while($row_expendit = mysql_fetch_assoc($query_expendit)){
						$expendit[] = $row_expendit;
					}
					
					$total_exp = 0;
					foreach ($exptype as $et){
						$total_row = 0;
						foreach ($expendit as $ex) if ($ex['exptype_id'] == $et['exptype_id']) $total_row = $total_row + $ex['exp_amount'];
						tr_colored($color);	//Function for alternating Row Colors
						echo '	<td>'.$et['exptype_type'].'</td>
										<td>'.number_format($total_row).' '.$_SESSION['set_cur'].'</td>
									</tr>';
						$total_exp = $total_exp + $total_row;	
						
						//Prepare data for export to Excel file
						array_push($_SESSION['rep_export'], array("Type" => $et['exptype_type'], "Amount" => $total_row));
					}
					echo '<tr class="balance">
									<td>Total Expenditures:</td>
									<td>'.number_format($total_exp).' '.$_SESSION['set_cur'].'</td>
								</tr>';
			}
			
			/*** CASE 2: Detailed Report ***/
			else{
				$sql_expendit = "SELECT * FROM expenditures, exptype WHERE expenditures.exptype_id = exptype.exptype_id AND exp_date BETWEEN $firstDay AND $lastDay ORDER BY exp_date";
				$query_expendit = mysql_query($sql_expendit);
				check_sql($query_expendit);
				?>
										
				<!-- Export Button -->					
				<form class="export" action="rep_export.php" method="post">
					<input type="submit" name="export_rep" value="Export Report" />
				</form>
				
				<!-- TABLE: Results -->
				<table id="tb_table">
					<colspan>
						<col width="10%">
						<col width="15%">
						<col width="20%">
						<col width="20%">
						<col width="10%">
						<col width="10%">
						<col width="15%">
					</colspan>
					<tr>
						<th class="title" colspan="7">Detailed Expenditures for <?PHP echo $rep_month.'/'.$rep_year ?></th>
					</tr>
					<tr>
						<th>Date</th>
						<th>Type</th>
						<th>Recipient</th>
						<th>Details</th>
						<th>Receipt No.</th>
						<th>Voucher No.</th>
						<th>Amount</th>
					</tr>
					<?PHP
					$total_exp = 0;
					while($row_expendit = mysql_fetch_assoc($query_expendit)){
						tr_colored($color);	//Function for alternating Row Colors
						echo '	<td>'.date("d.m.Y",$row_expendit['exp_date']).'</td>
										<td>'.$row_expendit['exptype_type'].'</td>
										<td>'.$row_expendit['exp_recipient'].'</td>
										<td>'.$row_expendit['exp_text'].'</td>
										<td>'.$row_expendit['exp_receipt'].'</td>
										<td>'.$row_expendit['exp_voucher'].'</td>
										<td>'.number_format($row_expendit['exp_amount']).' '.$_SESSION['set_cur'].'</td>
									</tr>';
						$total_exp = $total_exp + $row_expendit['exp_amount'];
						
						//Prepare data for export to Excel file
						array_push($_SESSION['rep_export'], array("Date" => date("d.m.Y",$row_expendit['exp_date']), "Type" => $row_expendit['exptype_type'], "Recipient" => $row_expendit['exp_recipient'], "Details" => $row_expendit['exp_text'], "Receipt No" => $row_expendit['exp_receipt'], "Voucher No" => $row_expendit['exp_voucher'],"Amount" => $row_expendit['exp_amount']));
					}
					echo '<tr class="balance">
									<td colspan="7">Total Expenditures: '.number_format($total_exp).' '.$_SESSION['set_cur'].'</td>
								</tr>';
			}
		}
		?>
		</table>
	</body>
</html>