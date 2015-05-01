<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/UuidFactory.php');

//Used by /collections/misc/collprofiles.php page
class CollectionProfileManager {

	private $conn;
	private $collid;
	private $errorStr;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollid($collid){
		if($collid && is_numeric($collid)){
			$this->collid = $this->cleanInStr($collid);
			return true;
		}
		return false; 
	}

	public function getCollectionData($filterForForm = 0){
		$returnArr = Array();
		if($this->collid){
			$sql = "SELECT c.institutioncode, i.InstitutionName, ".
				"i.Address1, i.Address2, i.City, i.StateProvince, i.PostalCode, i.Country, i.Phone, ".
				"c.collid, c.CollectionCode, c.CollectionName, ".
				"c.FullDescription, c.Homepage, c.individualurl, c.Contact, c.email, ".
				"c.latitudedecimal, c.longitudedecimal, c.icon, c.colltype, c.managementtype, c.publicedits, ".
				"c.guidtarget, c.rights, c.rightsholder, c.accessrights, c.sortseq, cs.uploaddate, ".
				"IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
				"IFNULL(cs.familycnt,0) AS familycnt, IFNULL(cs.genuscnt,0) AS genuscnt, IFNULL(cs.speciescnt,0) AS speciescnt, ".
				"c.securitykey, c.collectionguid ".
				"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
				"LEFT JOIN institutions i ON c.iid = i.iid ".
				"WHERE (c.collid = ".$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['collectioncode'] = $row->CollectionCode;
				$returnArr['collectionname'] = $row->CollectionName;
				$returnArr['institutionname'] = $row->InstitutionName;
				$returnArr['address2'] = $row->Address1;
				$returnArr['address1'] = $row->Address2;
				$returnArr['city'] = $row->City;
				$returnArr['stateprovince'] = $row->StateProvince;
				$returnArr['postalcode'] = $row->PostalCode;
				$returnArr['country'] = $row->Country;
				$returnArr['phone'] = $row->Phone;
				$returnArr['fulldescription'] = $row->FullDescription;
				$returnArr['homepage'] = $row->Homepage;
				$returnArr['individualurl'] = $row->individualurl;
				$returnArr['contact'] = $row->Contact;
				$returnArr['email'] = $row->email;
				$returnArr['latitudedecimal'] = $row->latitudedecimal;
				$returnArr['longitudedecimal'] = $row->longitudedecimal;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
				$returnArr['publicedits'] = $row->publicedits;
				$returnArr['guidtarget'] = $row->guidtarget;
				$returnArr['rights'] = $row->rights;
				$returnArr['rightsholder'] = $row->rightsholder;
				$returnArr['accessrights'] = $row->accessrights;
				$returnArr['sortseq'] = $row->sortseq;
				$returnArr['skey'] = $row->securitykey;
				$returnArr['guid'] = $row->collectionguid;
				$uDate = "";
				if($row->uploaddate){
					$uDate = $row->uploaddate;
					$month = substr($uDate,5,2);
					$day = substr($uDate,8,2);
					$year = substr($uDate,0,4);
					$uDate = date("j F Y",mktime(0,0,0,$month,$day,$year));
				}
				$returnArr['uploaddate'] = $uDate;
				$returnArr['recordcnt'] = $row->recordcnt;
				$returnArr['georefpercent'] = ($returnArr['recordcnt']?round(($row->georefcnt/$returnArr['recordcnt'])*100):0);
				$returnArr['familycnt'] = $row->familycnt;
				$returnArr['genuscnt'] = $row->genuscnt;
				$returnArr['speciescnt'] = $row->speciescnt;
			}
			$rs->free();
			//Get categories
			$sql = 'SELECT ccpk '.
				'FROM omcollcatlink '.
				'WHERE (collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['ccpk'] = $r->ccpk;
			}
			$rs->free();
			//Get additional statistics
			$sql = 'SELECT count(DISTINCT o.occid) as imgcnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['imgpercent'] = ($returnArr['recordcnt']?round(($row->imgcnt/$returnArr['recordcnt'])*100):0);
			}
			$rs->free();
			//BOLD count
			$sql = 'SELECT count(g.occid) as boldcnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.boldsystems%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['boldcnt'] = $r->boldcnt;
			}
			$rs->free();
			//GenBank count
			$sql = 'SELECT count(g.occid) as gencnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.ncbi%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['gencnt'] = $r->gencnt;
			}
			$rs->free();
			//Reference count
			$sql = 'SELECT count(r.occid) as refcnt '.
				'FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['refcnt'] = $r->refcnt;
			}
			$rs->free();
			//Check to make sure Security Key and collection GUIDs exist
			if(!$returnArr['guid']){
				$returnArr['guid'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET collectionguid = "'.$returnArr['guid'].'" '.
					'WHERE collectionguid IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}
			if(!$returnArr['skey']){
				$returnArr['skey'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET securitykey = "'.$returnArr['skey'].'" '.
					'WHERE securitykey IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}  
		}
		if($filterForForm){
			$this->cleanOutArr($returnArr);
		}
		return $returnArr;
	}

	public function submitCollEdits($postArr){
		$status = true;
		if($this->collid){
			$instCode = $this->cleanInStr($postArr['institutioncode']);
			$collCode = $this->cleanInStr($postArr['collectioncode']);
			$coleName = $this->cleanInStr($postArr['collectionname']);
			$fullDesc = $this->cleanInStr($postArr['fulldescription']);
			$homepage = $this->cleanInStr($postArr['homepage']);
			$contact = $this->cleanInStr($postArr['contact']);
			$email = $this->cleanInStr($postArr['email']);
			$publicEdits = (array_key_exists('publicedits',$postArr)?$postArr['publicedits']:0);
			$guidTarget = (array_key_exists('guidtarget',$postArr)?$postArr['guidtarget']:'');
			$rights = $this->cleanInStr($postArr['rights']);
			$rightsHolder = $this->cleanInStr($postArr['rightsholder']);
			$accessRights = $this->cleanInStr($postArr['accessrights']);
			$icon = $this->cleanInStr($postArr['icon']);
			$indUrl = $this->cleanInStr($postArr['individualurl']);
			
			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections '.
				'SET institutioncode = "'.$instCode.'",'.
				'collectioncode = '.($collCode?'"'.$collCode.'"':'NULL').','.
				'collectionname = "'.$coleName.'",'.
				'fulldescription = '.($fullDesc?'"'.$fullDesc.'"':'NULL').','.
				'homepage = '.($homepage?'"'.$homepage.'"':'NULL').','.
				'contact = '.($contact?'"'.$contact.'"':'NULL').','.
				'email = '.($email?'"'.$email.'"':'NULL').','.
				'latitudedecimal = '.($postArr['latitudedecimal']?$postArr['latitudedecimal']:'NULL').','.
				'longitudedecimal = '.($postArr['longitudedecimal']?$postArr['longitudedecimal']:'NULL').','.
				'publicedits = '.$publicEdits.','.
				'guidtarget = '.($guidTarget?'"'.$guidTarget.'"':'NULL').','.
				'rights = '.($rights?'"'.$rights.'"':'NULL').','.
				'rightsholder = '.($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
				'accessrights = '.($accessRights?'"'.$accessRights.'"':'NULL').', '.
				'icon = '.($icon?'"'.$icon.'"':'NULL').', '.
				'individualurl = '.($indUrl?'"'.$indUrl.'"':'NULL').' ';
			if(array_key_exists('colltype',$postArr)){
				$sql .= ',managementtype = "'.$postArr['managementtype'].'",'.
					'colltype = "'.$postArr['colltype'].'",'.
					'sortseq = '.($postArr['sortseq']?$postArr['sortseq']:'NULL').' ';
			}
			$sql .= 'WHERE (collid = '.$this->collid.')';
			//echo $sql; exit;
			if(!$conn->query($sql)){
				$status = 'ERROR updating collection: '.$conn->error;
				return $status;
			}
			
			//Modify collection category, if needed
			if(isset($postArr['ccpk']) && $postArr['ccpk']){
				$rs = $conn->query('SELECT ccpk FROM omcollcatlink WHERE collid = '.$this->collid);
				if($r = $rs->fetch_object()){
					if($r->ccpk <> $postArr['ccpk']){
						if(!$conn->query('UPDATE omcollcatlink SET ccpk = '.$postArr['ccpk'].' WHERE ccpk = '.$r->ccpk.' AND collid = '.$this->collid)){
							$status = 'ERROR updating collection category link: '.$conn->error;
							return $status;
						}
					}
				}
				else{
					if(!$conn->query('INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$postArr['ccpk'].','.$this->collid.')')){
						$status = 'ERROR inserting collection category link(1): '.$conn->error;
						return $status;
					}
				}
			}			
			$conn->close();
		}
		return $status;
	}

	public function submitCollAdd($postArr){
		global $symbUid;
		$instCode = $this->cleanInStr($postArr['institutioncode']);
		$collCode = $this->cleanInStr($postArr['collectioncode']);
		$coleName = $this->cleanInStr($postArr['collectionname']);
		$fullDesc = $this->cleanInStr($postArr['fulldescription']);
		$homepage = $this->cleanInStr($postArr['homepage']);
		$contact = $this->cleanInStr($postArr['contact']);
		$email = $this->cleanInStr($postArr['email']);
		$rights = $this->cleanInStr($postArr['rights']);
		$rightsHolder = $this->cleanInStr($postArr['rightsholder']);
		$accessRights = $this->cleanInStr($postArr['accessrights']);
		$publicEdits = (array_key_exists('publicedits',$postArr)?$postArr['publicedits']:0);
		$guidTarget = (array_key_exists('guidtarget',$postArr)?$postArr['guidtarget']:'');
		$icon = array_key_exists('icon',$postArr)?$this->cleanInStr($postArr['icon']):'';
		$managementType = array_key_exists('managementtype',$postArr)?$this->cleanInStr($postArr['managementtype']):'';
		$collType = array_key_exists('colltype',$postArr)?$this->cleanInStr($postArr['colltype']):'';
		$guid = array_key_exists('collectionguid',$postArr)?$this->cleanInStr($postArr['collectionguid']):'';
		if(!$guid) $guid = UuidFactory::getUuidV4();
		$indUrl = array_key_exists('individualurl',$postArr)?$this->cleanInStr($postArr['individualurl']):'';
		$sortSeq = array_key_exists('sortseq',$postArr)?$postArr['sortseq']:'';
		
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omcollections(institutioncode,collectioncode,collectionname,fulldescription,homepage,'.
			'contact,email,latitudedecimal,longitudedecimal,publicedits,guidtarget,rights,rightsholder,accessrights,icon,'.
			'managementtype,colltype,collectionguid,individualurl,sortseq) '.
			'VALUES ("'.$instCode.'",'.
			($collCode?'"'.$collCode.'"':'NULL').',"'.
			$coleName.'",'.
			($fullDesc?'"'.$fullDesc.'"':'NULL').','.
			($homepage?'"'.$homepage.'"':'NULL').','.
			($contact?'"'.$contact.'"':'NULL').','.
			($email?'"'.$email.'"':'NULL').','.
			($postArr['latitudedecimal']?$postArr['latitudedecimal']:'NULL').','.
			($postArr['longitudedecimal']?$postArr['longitudedecimal']:'NULL').','.
			$publicEdits.','.($guidTarget?'"'.$guidTarget.'"':'NULL').','.
			($rights?'"'.$rights.'"':'NULL').','.
			($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
			($accessRights?'"'.$accessRights.'"':'NULL').','.
			($icon?'"'.$icon.'"':'NULL').','.
			($managementType?'"'.$managementType.'"':'Snapshot').','.
			($collType?'"'.$collType.'"':'Preserved Specimens').',"'.
			$guid.'",'.($indUrl?'"'.$indUrl.'"':'NULL').','.
			($sortSeq?$sortSeq:'NULL').') ';
		//echo "<div>$sql</div>";
		$cid = 0;
		if($conn->query($sql)){
			$cid = $conn->insert_id;
			$sql = 'INSERT INTO omcollectionstats(collid,recordcnt,uploadedby) '.
				'VALUES('.$cid.',0,"'.$symbUid.'")';
			$conn->query($sql);
			//Add collection to category
			if(isset($postArr['ccpk']) && $postArr['ccpk']){
				$sql = 'INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$postArr['ccpk'].','.$cid.')';
				if(!$conn->query($sql)){
					$status = 'ERROR inserting collection category link(2): '.$conn->error.'; SQL: '.$sql;
					return $status;
				}
			}
			$this->collid = $cid;
		}
		else{
			$cid = 'ERROR inserting new collection: '.$conn->error;
		}
		$conn->close();
		return $cid;
	}

	public function getAddresses(){
		$retArr = Array();
		if($this->collid){
			$sql = 'SELECT i.iid, i.institutioncode, i.institutionname, i.address1, i.address2, '.
				'i.city, i.stateprovince, i.postalcode, i.country, i.phone, i.contact, i.email, i.url, i.notes '.
				'FROM institutions i INNER JOIN omcollections c ON i.iid = c.iid '.
				'WHERE (c.collid = '.$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->iid]['institutioncode'] = $r->institutioncode;
				$retArr[$r->iid]['institutionname'] = $r->institutionname;
				$retArr[$r->iid]['address1'] = $r->address1;
				$retArr[$r->iid]['address2'] = $r->address2;
				$retArr[$r->iid]['city'] = $r->city;
				$retArr[$r->iid]['stateprovince'] = $r->stateprovince;
				$retArr[$r->iid]['postalcode'] = $r->postalcode;
				$retArr[$r->iid]['country'] = $r->country;
				$retArr[$r->iid]['phone'] = $r->phone;
				$retArr[$r->iid]['contact'] = $r->contact;
				$retArr[$r->iid]['email'] = $r->email;
				$retArr[$r->iid]['url'] = $r->url;
				$retArr[$r->iid]['notes'] = $r->notes;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function linkAddress($addIID){
		$status = false;
		if($this->collid && is_numeric($addIID)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections SET iid = '.$addIID.' WHERE collid = '.$this->collid;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR linking institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	public function removeAddress($removeIID){
		$status = false;
		if($this->collid && is_numeric($removeIID)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections SET iid = NULL '.
				'WHERE collid = '.$this->collid.' AND iid = '.$removeIID;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR removing institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	public function updateStatistics(){
		set_time_limit(200);
		$writeConn = MySQLiConnectionFactory::getCon("write");

		echo '<li>Updating specimen taxon links... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.TidInterpreted = t.tid '.
			'WHERE o.TidInterpreted IS NULL';
		$writeConn->query($sql);

		echo '<li>Update specimen image taxon links ... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'SET i.tid = o.tidinterpreted '.
			'WHERE o.tidinterpreted IS NOT NULL AND (i.tid IS NULL OR o.tidinterpreted <> i.tid)';
		$writeConn->query($sql);

		echo '<li>Updating records with null families... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'SET o.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (o.family IS NULL OR o.family = "")';
		$writeConn->query($sql);
		echo $writeConn->affected_rows.' records updated</li>';

		/*
		echo '<li>Updating records with null author... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
			'SET o.scientificNameAuthorship = t.author '.
			'WHERE o.scientificNameAuthorship IS NULL and t.author is not null';
		$writeConn->query($sql);
		echo $writeConn->affected_rows.' records updated</li>';
		*/
		
		echo '<li>Updating total record count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li>Updating family count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li>Updating genus count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collid.') AND t.rankid IN(180,220,230,240,260)) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li>';
		
		echo '<li>Updating species count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collid.') AND t.rankid IN(220,230,240,260)) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done</li>';
		
		echo '<li>Updating georeference count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '.
			'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li>';
		
		/*
		echo '<li>Updating georeference indexing... ';
		ob_flush();
		flush();
		$sql = 'REPLACE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '.
			'FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL '.
			'AND o.decimallongitude IS NOT NULL';
		$writeConn->query($sql);
		
		$sql = 'DELETE FROM omoccurgeoindex WHERE InitialTimestamp < DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
		$writeConn->query($sql);
		echo 'Done!</li>';
		*/
		
		echo '<li>Finished updating collection statistics</li>';
	}

	public function getTaxonCounts($f=''){
		$family = $this->cleanInStr($f);
		$returnArr = Array();
		$sql = '';
		if($family){
			/*
			$sql = 'SELECT t.unitname1 as taxon, Count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'GROUP BY o.CollID, t.unitname1, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family = "'.$family.'") AND (t.unitname1 != "'.$family.'") '.
				'ORDER BY t.unitname1';
			*/
			$sql = 'SELECT t.unitname1 as taxon, Count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Family = "'.$family.'") AND (t.unitname1 != "'.$family.'") '.
				'GROUP BY o.CollID, t.unitname1, o.Family ';
		}
		else{
			/*
			$sql = 'SELECT o.family as taxon, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family IS NOT NULL) AND (o.Family <> "") '.
				'ORDER BY o.Family';
			*/
			$sql = 'SELECT o.family as taxon, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Family IS NOT NULL) AND (o.Family <> "") '.
				'GROUP BY o.CollID, o.Family ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->taxon] = $row->cnt;
		}
		$rs->free();
		asort($returnArr);
		return $returnArr;
	}

	public function getGeographicCounts($c="",$s=""){
		$returnArr = Array();
		$country = $this->cleanInStr($c);
		$state = $this->cleanInStr($s);
		$sql = '';
		if($country){
			$sql = 'SELECT o.stateprovince as termstr, Count(*) AS cnt '. 
				'FROM omoccurrences o '. 
				'WHERE (o.CollID = '.$this->collid.') AND (o.StateProvince IS NOT NULL) AND (o.country = "'.$country.'") '.
				'GROUP BY o.StateProvince, o.country';
			/*
			$sql = 'SELECT trim(o.stateprovince) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.StateProvince, o.country '.
				'HAVING (o.CollID = '.$this->collid.') AND (o.StateProvince IS NOT NULL) AND (o.StateProvince <> "") '.
				'AND (o.country = "'.$country.'") '.
				'ORDER BY trim(o.StateProvince)';
				*/
		}
		elseif($state){
			$sql = 'SELECT o.county as termstr, Count(*) AS cnt '. 
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.county IS NOT NULL) AND (o.stateprovince = "'.$state.'") '.
				'GROUP BY o.StateProvince, o.county';
			/*
			$sql = 'SELECT trim(o.county) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.StateProvince, o.county '.
				'HAVING (o.CollID = '.$this->collid.') AND (o.county IS NOT NULL) AND (o.county <> "") '.
				'AND (o.stateprovince = "'.$state.'") '.
				'ORDER BY trim(o.county)';
				*/
		}
		else{
			$sql = 'SELECT o.country as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Country IS NOT NULL) '.
				'GROUP BY o.Country ';
			/*
			$sql = 'SELECT trim(o.country) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.Country '.
				'HAVING (o.CollID = '.$this->collid.') AND o.Country IS NOT NULL AND o.Country <> "" '.
				'ORDER BY trim(o.Country)';
				*/
		}
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$t = $row->termstr;
			if($state){
				$t = trim(str_ireplace(array(' county',' co.',' Counties'),'',$t));
			}
			if($t){
				$returnArr[$t] = $row->cnt;
			}
		}
		$rs->close();
		ksort($returnArr);
		return $returnArr;
	}
	
	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT iid,institutionname,institutioncode '.
			'FROM institutions '.
			'ORDER BY institutionname,institutioncode ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->iid] = $r->institutionname.' ('.$r->institutioncode.')';
		}
		return $retArr;
	}
	
	public function getCategoryArr(){
		$retArr = array();
		$sql = 'SELECT ccpk, category '.
			'FROM omcollcategories '.
			'ORDER BY category ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->ccpk] = $r->category;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getCollectionList(){
		$returnArr = Array();
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, '.
			'c.fulldescription, c.homepage, c.contact, c.email, c.icon, c.collectionguid '.
			'FROM omcollections c INNER JOIN omcollectionstats s ON c.collid = s.collid '.
			'WHERE s.recordcnt > 0 '.
			'ORDER BY c.SortSeq,c.CollectionName';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]['institutioncode'] = $row->institutioncode;
			$returnArr[$row->collid]['collectioncode'] = $row->collectioncode;
			$returnArr[$row->collid]['collectionname'] = $row->collectionname;
			$returnArr[$row->collid]['fulldescription'] = $row->fulldescription;
			$returnArr[$row->collid]['homepage'] = $row->homepage;
			$returnArr[$row->collid]['contact'] = $row->contact;
			$returnArr[$row->collid]['email'] = $row->email;
			$returnArr[$row->collid]['icon'] = $row->icon;
			$returnArr[$row->collid]['guid'] = $row->collectionguid;
		}
		$rs->free();
		return $returnArr;
	}

	//Used to index specimen records for particular collection
	public function echoOccurrenceListing($s, $l){
		global $clientRoot;
		$start = $this->cleanInStr($s);
		$limit = $this->cleanInStr($l);
		if(substr($clientRoot,-1) != '/') $clientRoot .= '/';
		if($this->collid){
			//Get count
			$occCnt = 0;
			if(!is_numeric($start)){
				$sql = 'SELECT count(*) AS cnt FROM omoccurrences WHERE collid = '.$this->collid.' ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$occCnt = $r->cnt;
				}
				$rs->free();
				if($occCnt < $limit) $start = 0;
			}
			
			if(is_numeric($start)){
				$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid, o.sciname, o.recordedby, o.recordnumber, g.guid '.
					'FROM omoccurrences o INNER JOIN guidoccurrences g ON o.occid = g.occid '.
					'WHERE collid = '.$this->collid.' '.
					'ORDER BY o.catalognumber,o.occid '.
					'LIMIT '.$start.','.$limit;
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					echo '<div style="margin:5px;">';
					echo '<div><b>Collector:</b> '.$r->recordedby.' '.$r->recordnumber.'</div>';
					echo '<div style="margin-left:10px;"><b>Scientific Name:</b> '.$r->sciname.'</div>';
					echo '<div style="margin-left:10px;"><b>Identifiers:</b> '.$r->catalognumber.' '.$r->occurrenceid.'</div>';
					//echo '<div style="margin-left:10px;"><b>GUID:</b> '.$r->guid.'</div>';
					echo '<div style="margin-left:10px;"><a href="'.$clientRoot.'/collections/individual/index.php?occid='.$r->occid.'" target="_blank"><b>Full Details</b></a></div>';
					echo '</div>';
				}
				$rs->free();
			}
			else{
				for($j = 0;$j < $occCnt;$j += $limit){
					$endCnt = (($j+$limit)<$occCnt?($j+$limit):$occCnt);
					echo '<div><a href="collectionindex.php?collid='.$this->collid.'&start='.$j.'&limit='.$limit.'">Records '.($j+1).' - '.$endCnt.'</a></div>';
				}
			}
		}
	}

	public function getErrorStr(){
		return $this->errorStr;
	}
	
	private function cleanOutArr(&$arr){
		foreach($arr as $k => $v){
			$arr[$k] = $this->cleanOutStr($v);
		}
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>