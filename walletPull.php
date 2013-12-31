<?php
include('settings.php');

$pdo = new PDO("mysql:dbname=$databaseName;host=$databaseHost;charset=utf8", $databaseUsername, $databasePassword);

$walletXml=new SimpleXMLElement(file_get_contents("https://api.eveonline.com/corp/WalletJournal.xml.aspx?keyID=$keyID&vCode=$vCode"));
foreach ($walletXml->result->rowset->row as $key => $value) {
	if($value['refTypeID']==37){
		$stmt = $pdo->prepare('INSERT INTO evetransactions (date,refID,characterName,characterID,amount,transactionType,processed) VALUES (:date,:refID,:characterName,:characterID,:amount,:transactionType,0)');
		$stmt->execute(array('date' => $value['date'], 'refID' => $value['refID'], 'characterName'=> $value['ownerName2'],'characterID' => $value['ownerID2'],'transactionType' => $value['refTypeID'],'amount' => $value['amount']));
	}else{
		$stmt = $pdo->prepare('INSERT INTO evetransactions (date,refID,characterName,characterID,amount,transactionType,processed) VALUES (:date,:refID,:characterName,:characterID,:amount,:transactionType,0)');
		$stmt->execute(array('date' => $value['date'], 'refID' => $value['refID'], 'characterName'=> $value['ownerName1'],'characterID' => $value['ownerID1'],'transactionType' => $value['refTypeID'],'amount' => $value['amount']));
	}
	
}

$stmtPullTransactions = $pdo->prepare('SELECT * FROM evetransactions WHERE processed = :processed AND transactionType="10"');
$stmtPullTransactions ->execute(array('processed' => '0'));	
while ($row = $stmtPullTransactions->fetch(PDO::FETCH_ASSOC)) {
	$stmt = $pdo->prepare('INSERT INTO account (characterName,characterID,balance,password) VALUES (:characterName,:characterID,:amount,:password) ON DUPLICATE KEY UPDATE balance=balance+:amount');
	$stmt->execute(array('characterName' => $row['characterName'], 'characterID' => $row['characterID'], 'amount' => $row['amount'],'password' => "abcd79400b926610c47d97570ebd5856"));
	$stmt = $pdo->prepare('UPDATE evetransactions SET processed=1 WHERE refID=:refID');
	$stmt->execute(array('refID' => $row['refID']));
}

$stmtPullWithdraw = $pdo->prepare('SELECT * FROM withdraws WHERE status = :status');
$stmtPullWithdraw ->execute(array('status' => '3'));	
while ($row = $stmtPullWithdraw->fetch(PDO::FETCH_ASSOC)) {
	$amount = floatval("-".$row['amount']);
	$uuid = $row['uuid'];
	$stmt = $pdo->prepare('SELECT * FROM evetransactions WHERE processed = "0" AND characterID = :characterID AND transactionType = "37" AND amount =:amount');
	$stmt->execute(array('characterID' => $row['characterID'], 'amount' => $amount));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if($row){
		$stmt = $pdo->prepare('UPDATE withdraws SET status="4", refID=:refID WHERE uuid = :uuid');
		$stmt->execute(array('refID' => $row['refID'], 'uuid' => $uuid));
		$stmt = $pdo->prepare('UPDATE evetransactions SET processed="1" WHERE refID=:refID');
		$stmt->execute(array('refID' => $row['refID']));
	}
	
}
?>