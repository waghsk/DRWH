<?
/*
    Dr Warehouse is a document oriented data warehouse for clinicians. 
    Copyright (C) 2017  Nicolas Garcelon

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    Contact : Nicolas Garcelon - nicolas.garcelon@institutimagine.org
    Institut Imagine
    24 boulevard du Montparnasse
    75015 Paris
    France
*/
if ($_SESSION['dwh_login']=='' && !preg_match("/connexion_user\.php/",$_SERVER['REQUEST_URI']) && !preg_match("/contact\.php/",$_SERVER['REQUEST_URI']) && !preg_match("/ajax\.php/",$_SERVER['REQUEST_URI'])) {
	header("Location: connexion_user.php?script_appel=".preg_replace('/&/','ETCOMMERCIAL',$_SERVER['REQUEST_URI']));
	exit;
}
$erreur_droit="";

$login_session=$_SESSION['dwh_login'];

$sel_var1=oci_parse($dbh,"select user_num,firstname,lastname,mail,user_phone_number from  dwh_user where login='$login_session' ");
oci_execute($sel_var1);
$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
$user_num_session=$r['USER_NUM'];
$firstname_user_session=$r['FIRSTNAME'];
$lastname_user_session=$r['LASTNAME'];
$mail_session=$r['MAIL'];
$user_phone_number_session=$r['USER_PHONE_NUMBER'];

$_SESSION['dwh_user_num']=$user_num_session;
$_SESSION['dwh_firstname_user']=$firstname_user_session;
$_SESSION['dwh_lastname_user']=$lastname_user_session;
$_SESSION['dwh_mail']=$mail_session;
$_SESSION['dwh_user_phone_number']=$user_phone_number_session;
//REINITIALISATION//
foreach ($tableau_user_droit as $right) { 
	$_SESSION['dwh_droit_'.$right.'0']='';
	$_SESSION['dwh_droit_'.$right]='';
}

//// LES DROITS GLOBAUX DE L'UTILISATEUR //////////
$sel_var1=oci_parse($dbh,"select user_profile from dwh_user_profile where user_num in (select user_num from dwh_user where login='$login_session')");
oci_execute($sel_var1);
while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$user_profile=$r['USER_PROFILE'];
	$_SESSION['dwh_profil_'.$user_profile]='ok';

	$sel_vardroit=oci_parse($dbh,"select right from dwh_profile_right where user_profile='$user_profile'");
	oci_execute($sel_vardroit);
	while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
		$right=$r_droit['RIGHT'];
		$_SESSION['dwh_droit_'.$right.'0']='ok';
		$_SESSION['dwh_droit_'.$right]='ok';
	}
	
}
if ($_SESSION['dwh_droit_see_detailed0']=='ok') {
	$_SESSION['dwh_droit_add_patient0']='ok';
}

//// LES SERVICES DE L'UTILISATEUR //////////
$liste_service_session='';
$liste_uf_session='';
$sel_var1=oci_parse($dbh,"select department_num from dwh_user_department where user_num='$user_num_session' ");
oci_execute($sel_var1);
while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$department_num_session=$r['DEPARTMENT_NUM'];
	$liste_service_session.="$department_num_session,";
	$sel_var_uf=oci_parse($dbh,"select unit_code from dwh_thesaurus_unit where department_num='$department_num_session' ");
	oci_execute($sel_var_uf);
	while ($r_uf=oci_fetch_array($sel_var_uf,OCI_RETURN_NULLS+OCI_ASSOC)) {
		$unit_code=$r_uf['UNIT_CODE'];
		$liste_uf_session.="'$unit_code',";
	}
}
$liste_service_session=substr($liste_service_session,0,-1);
$liste_uf_session=substr($liste_uf_session,0,-1);





//// LES TYPES DOC DE L'UTILISATEUR //////////
$liste_document_origin_code_session='';
$sel_var1=oci_parse($dbh,"select distinct document_origin_code from dwh_profile_document_origin, dwh_user_profile where user_num='$user_num_session' and dwh_profile_document_origin.user_profile= dwh_user_profile.user_profile");
oci_execute($sel_var1);
while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$document_origin_code=$r['DOCUMENT_ORIGIN_CODE'];
	$liste_document_origin_code_session.="'$document_origin_code',";
}
$liste_document_origin_code_session=substr($liste_document_origin_code_session,0,-1);



/// Acc�der au contenu d'une cohorte pour moteur
if ($_GET['action']=='rechercher_dans_cohorte') {
	$cohort_num=$_GET['cohort_num'];
	$autorisation_cohorte_voir=autorisation_cohorte_voir($cohort_num,$user_num_session);
	if ($autorisation_cohorte_voir=='ok') {
		$sel_var1=oci_parse($dbh,"select dwh_seq.nextval datamart_num from dual  ");
		oci_execute($sel_var1);
		$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
		$num_datamart_insert=$r['DATAMART_NUM'];
		$sel_var1=oci_parse($dbh,"select title_cohort,description_cohort,datamart_num from dwh_cohort where cohort_num=$cohort_num  ");
		oci_execute($sel_var1);
		$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
		$title_cohort=nettoyer_pour_inserer($r['TITLE_COHORT']);
		$description_cohort=nettoyer_pour_inserer($r['DESCRIPTION_COHORT']);
		$datamart_num_origin=$r['DATAMART_NUM'];
		
		insert_datamart ($num_datamart_insert,"Cohorte $title_cohort",$description_cohort,'sysdate','sysdate','sysdate+1',1,$datamart_num_origin);
		
		$sel_var1=oci_parse($dbh,"select right from dwh_cohort_user_right where cohort_num=$cohort_num and user_num=$user_num_session");
		oci_execute($sel_var1) ;
		while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$right=nettoyer_pour_inserer($r['RIGHT']);
			if ($right!='' && $user_num_session!='') {
				insert_datamart_user_droit ($num_datamart_insert,$user_num_session,$right);
			}
		}
		insert_datamart_resultat ("select distinct $num_datamart_insert,patient_num from dwh_cohort_result  where cohort_num=$cohort_num and status=1");
		
		$_POST['datamart_num']=$num_datamart_insert;
		$_GET['datamart_num']=$num_datamart_insert;
	}
}

/// Acc�der au contenu d'un r�sultat pour affiner la recherche
if ($_GET['action']=='rechercher_dans_resultat') {
	$tmpresult_num=$_GET['tmpresult_num'];
	$datamart_num=$_GET['datamart_num'];
	$sel_var1=oci_parse($dbh,"select dwh_seq.nextval datamart_num from dual  ");
	oci_execute($sel_var1);
	$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
	$num_datamart_insert=$r['DATAMART_NUM'];
	
	insert_datamart ($num_datamart_insert,'Affiner le r�sultat pr�c�dent','Affiner le r�sultat','sysdate','sysdate','sysdate+1',1,$datamart_num);
	if ($datamart_num!=0) {
		$sel_vardroit=oci_parse($dbh,"select right from dwh_datamart_user_right where user_num='$user_num_session' and datamart_num=$datamart_num");
		oci_execute($sel_vardroit);
		while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$right=$r_droit['RIGHT'];
			if ($right!='' && $user_num_session!='') {
				insert_datamart_user_droit ($num_datamart_insert,$user_num_session,$right);
			}
		}
		$liste_document_origin_code_session='';
		$sel_vardroit=oci_parse($dbh,"select document_origin_code from dwh_datamart_doc_origin where datamart_num=$datamart_num");
		oci_execute($sel_vardroit);
		while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$document_origin_code=$r_droit['DOCUMENT_ORIGIN_CODE'];
			insert_datamart_document_origin_code ($num_datamart_insert,$document_origin_code);
			$liste_document_origin_code_session.="'$document_origin_code',";
		}
		$liste_document_origin_code_session=substr($liste_document_origin_code_session,0,-1);
		
	} else {
		$sel_var1=oci_parse($dbh,"select user_profile from dwh_user_profile where user_num in (select user_num from dwh_user where login='$login_session')");
		oci_execute($sel_var1);
		while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$user_profile=$r['USER_PROFILE'];
			$sel_vardroit=oci_parse($dbh,"select right from dwh_profile_right where user_profile='$user_profile'");
			oci_execute($sel_vardroit);
			while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$right=$r_droit['RIGHT'];
				if ($right!='' && $user_num_session!='') {
					insert_datamart_user_droit ($num_datamart_insert,$user_num_session,$right);
				}
			}
		}
	}
	$filtre_sql_resultat='';
        if ($liste_document_origin_code_session!='') {
        	if (!preg_match("/'tout'/i","$liste_document_origin_code_session")) {
                        $filtre_sql_resultat.=" and document_origin_code in ($liste_document_origin_code_session) ";
        	}
        } else if ($_SESSION['dwh_droit_admin_datamart0']=='') {
                $filtre_sql_resultat.=" and 1=2";
        }
        
        if ($_SESSION['dwh_droit_all_departments'.$datamart_num]=='') {
                if ($liste_service_session!='') {
                        $filtre_sql_resultat.=" and exists (select patient_num from dwh_document where department_num in ($liste_service_session) and  dwh_tmp_result.patient_num=dwh_document.patient_num) ";
                } else if ($_SESSION['dwh_droit_admin_datamart0']=='') {
                        $filtre_sql_resultat.=" and 1=2";
                }
        }
        
	insert_datamart_resultat ("select distinct $num_datamart_insert,patient_num from dwh_tmp_result where tmpresult_num=$tmpresult_num $filtre_sql_resultat");
	$_POST['datamart_num']=$num_datamart_insert;
	$_GET['datamart_num']=$num_datamart_insert;
}

/// Acc�der au contenu d'une requete sauvegardee sur une date particuliere de resultat
//query_num=84041188&load_date=12/2014
if ($_GET['action']=='rechercher_dans_requete_sauvegardee') {
	$query_num=$_GET['query_num'];
	$load_date=$_GET['load_date'];
	$autorisation_requete_voir=autorisation_requete_voir ($query_num,$user_num_session);
	if ($autorisation_requete_voir=='ok') {
		$sel_var1=oci_parse($dbh,"select dwh_seq.nextval datamart_num from dual  ");
		oci_execute($sel_var1);
		$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
		$num_datamart_insert=$r['DATAMART_NUM'];
		$sel_var1=oci_parse($dbh,"select title_query,datamart_num from dwh_query where query_num=$query_num  ");
		oci_execute($sel_var1);
		$r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC);
		$title_query=nettoyer_pour_inserer($r['TITLE_QUERY']);
		$datamart_num_origin=$r['DATAMART_NUM'];
		
		insert_datamart ($num_datamart_insert,"Requete $title_query $load_date",'','sysdate','sysdate','sysdate+1',1,$datamart_num_origin);
		
		if ($datamart_num_origin!=0) {
			$sel_vardroit=oci_parse($dbh,"select right from dwh_datamart_user_right where user_num='$user_num_session' and datamart_num=$datamart_num_origin");
			oci_execute($sel_vardroit);
			while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$right=$r_droit['RIGHT'];
				if ($right!='' && $user_num_session!='') {
					insert_datamart_user_droit ($num_datamart_insert,$user_num_session,$right);
				}
			}
			
			$sel_vardroit=oci_parse($dbh,"select document_origin_code from dwh_datamart_doc_origin where datamart_num=$datamart_num_origin");
			oci_execute($sel_vardroit);
			while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$document_origin_code=$r_droit['DOCUMENT_ORIGIN_CODE'];
				insert_datamart_document_origin_code ($num_datamart_insert,$document_origin_code);
			}
			
		} else {
			$sel_var1=oci_parse($dbh,"select user_profile from dwh_user_profile where user_num in (select user_num from dwh_user where login='$login_session')");
			oci_execute($sel_var1);
			while ($r=oci_fetch_array($sel_var1,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$user_profile=$r['USER_PROFILE'];
				$sel_vardroit=oci_parse($dbh,"select right from dwh_profile_right where user_profile='$user_profile'");
				oci_execute($sel_vardroit);
				while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
					$right=$r_droit['RIGHT'];
					if ($right!='' && $user_num_session!='') {
						insert_datamart_user_droit ($num_datamart_insert,$user_num_session,$right);
					}
				}
			}
		}
		$filtre_sql_resultat='';
		if ($datamart_num_origin==0) {
			$sel_vardroit=oci_parse($dbh,"select count(*) droit_all_departments from dwh_datamart_user_right where user_num='$user_num_session' and datamart_num=$num_datamart_insert and right='all_departments'");
			oci_execute($sel_vardroit);
			$r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC);
			$droit_all_departments=$r_droit['DROIT_ALL_DEPARTMENTS'];
		        if ($droit_all_departments==0) {
		                if ($liste_service_session!='') {
		                        $filtre_sql_resultat.=" and exists (select patient_num from dwh_document where department_num in ($liste_service_session) and  dwh_query_result.patient_num=dwh_document.patient_num) ";
		                } else {
		                        $filtre_sql_resultat.=" and 1=2";
		                }
		        }
		}
		
		
	        if ($load_date!='') {
			$filtre_sql_resultat.=" and to_char(load_date,'MM/YYYY')='$load_date' ";
	        }
		insert_datamart_resultat ("select distinct $num_datamart_insert,patient_num from dwh_query_result where query_num=$query_num $filtre_sql_resultat");
		
		$_POST['datamart_num']=$num_datamart_insert;
		$_GET['datamart_num']=$num_datamart_insert;
	}
}


/// Verification right datamart ou entrepot
if (($_GET['datamart_num']=='' && $_POST['datamart_num']=='') || $_GET['datamart_num']=='0' || $_POST['datamart_num']=='0') {
	$datamart_num=0;
	if ($_SESSION['dwh_droit_all_departments0']=='ok') {
		$titre_global=get_translation('ON_THE_ENTIRE_DATAWAREHOUSE',"Sur tout l'entrep�t");
		//$titre_global="Sur tout l'entrep�t";
	} else {
		$titre_global=" ".get_translation('ON_ALL_PATIENTS_OF_YOUR_HOSPITAL_DEPARTMENTS','Sur les patients de vos services')." ";
		if ($liste_service_session=='' && $_SESSION['dwh_droit_admin_datamart0']=='') {
			$erreur_droit="<strong style=\"color:red;\">".get_translation('NO_HOSPITAL_DEPARTMENT_FOUND',"Vous n'�tes rattach� � aucun service").". ".get_translation('CONTACT_ADMIN',"Veuillez contacter l'administrateur").".</strong>";
		}
	}
} else {
	if ($_GET['datamart_num']!='') {
		$datamart_num=$_GET['datamart_num'];
	}
	if ($_POST['datamart_num']!='') {
		$datamart_num=$_POST['datamart_num'];
	}
	
	if ($datamart_num!='' && $datamart_num!=0) {
		// REINITIALISATION RIGHT //
		foreach ($tableau_datamart_droit as $right) { 
			$_SESSION['dwh_droit_'.$right.$datamart_num]='';
		}
		
		// VERIFICATION RIGHT DATAMART //
		$sel_vardroit=oci_parse($dbh,"select count(*) as verif from dwh_datamart_user_right,dwh_datamart 
						where user_num='$user_num_session' 
						and dwh_datamart.datamart_num=$datamart_num 
						and dwh_datamart.datamart_num=dwh_datamart_user_right.datamart_num
						and sysdate>=date_start and sysdate<=end_date
						");
		oci_execute($sel_vardroit);
		$r=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC);
		$verif_datamart=$r['VERIF'];
		if ($verif_datamart>0) {
			$sel_vardroit=oci_parse($dbh,"select title_datamart,description_datamart,datamart_num_origin,temporary_status from dwh_datamart where  datamart_num=$datamart_num");
			oci_execute($sel_vardroit);
			$r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC);
			$title_datamart=$r_droit['TITLE_DATAMART'];
			$description_datamart=$r_droit['DESCRIPTION_DATAMART'];
			$datamart_temporaire=$r_droit['TEMPORARY_STATUS'];
			$datamart_num_origin=$r_droit['DATAMART_NUM_ORIGIN'];
			if ($datamart_temporaire==1) {
				$titre_global="$title_datamart";
			} else {
				$titre_global="Datamart $title_datamart";
			}
			$sel_vardroit=oci_parse($dbh,"select right from dwh_datamart_user_right where user_num='$user_num_session' and datamart_num=$datamart_num");
			oci_execute($sel_vardroit);
			while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$right=$r_droit['RIGHT'];
				$_SESSION['dwh_droit_'.$right.$datamart_num]='ok';
			}
			$_SESSION['dwh_droit_all_departments'.$datamart_num]='ok';
			if ($_SESSION['dwh_droit_see_detailed'.$datamart_num]=='ok') {
				$_SESSION['dwh_droit_add_patient'.$datamart_num]='ok';
			}
			
			$liste_document_origin_code_session_datamart='';
			$sel_vardroit=oci_parse($dbh,"select document_origin_code from dwh_datamart_doc_origin where datamart_num=$datamart_num");
			oci_execute($sel_vardroit);
			while ($r_droit=oci_fetch_array($sel_vardroit,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$liste_document_origin_code_session_datamart.="'".$r_droit['DOCUMENT_ORIGIN_CODE']."',";
			}
			$liste_document_origin_code_session_datamart=substr($liste_document_origin_code_session_datamart,0,-1);
			if ($liste_document_origin_code_session_datamart!='') {
				$liste_document_origin_code_session=$liste_document_origin_code_session_datamart;
			}
		} else {
			$erreur_droit="<strong style=\"color:red;\">".get_translation('YOU_CANNOT_ACCESS_THIS_DATAMART',"Vous n'avez pas le droit d'acc�der � ce datamart").".<br>".get_translation('CONTACT_ADMIN_TO_OPEN_RIGHTS','Veuillez contacter les administrateurs pour vous ouvrir les droits.')."</strong>";
		}
	}	
}


?>