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



$selval=oci_parse($dbh,"select 
			to_char (birth_date, 'mm') as mois,to_char (birth_date, 'yyyy') as an,to_char (birth_date, 'dd') as jour,
			to_char (death_date, 'mm') as death_datemois,to_char (death_date, 'yyyy') as death_datean,to_char (death_date, 'dd') as death_datejour,
			to_char (sysdate, 'mm') as sysdatemois,to_char (sysdate, 'yyyy') as sysdatean,	to_char (sysdate, 'dd') as sysdatejour,
			to_char (birth_date, 'dd/mm/yyyy') as birth_date ,
			to_char (death_date, 'dd/mm/yyyy') as death_date 
			from dwh_patient where patient_num=$patient_num");
oci_execute($selval);
$res=oci_fetch_array($selval,OCI_RETURN_NULLS+OCI_ASSOC);
$mois=mois_en_3lettre($res['MOIS']);
$jour=$res['JOUR'];
$an=$res['AN'];
$heure='00:00:00 GMT';
$birth_date="$mois $jour $an $heure";
$datenaisclair=$res['BIRTH_DATE'];


$death_datemois=mois_en_3lettre($res['DEATH_DATEMOIS']);
$death_datejour=$res['DEATH_DATEJOUR'];
$death_datean=$res['DEATH_DATEAN'];
$heure='00:00:00 GMT';
$death_date="$death_datemois $death_datejour $death_datean $heure";
$death_dateclair=$res['DEATH_DATE'];

$sysdatemois=mois_en_3lettre($res['SYSDATEMOIS']);
$sysdatejour=$res['SYSDATEJOUR'];
$sysdatean=$res['SYSDATEAN'];
$sysdateheure='00:00:00 GMT';
$sysdate="$sysdatemois $sysdatejour $sysdatean $sysdateheure";

$xml="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<data>
";

$json="{\"events\":[
";

$tableau_document_afficher=array();

$selval=oci_parse($dbh,"select distinct patient_num,encounter_num, to_char (entry_date,  'yyyy') as an_deb,to_char (entry_date,  'mm') as mois_deb,to_char (entry_date,  'dd') as jour_deb,to_char (entry_date,  'HH24:MI') as heure_deb,to_char (out_date,  'yyyy') as an_sortie,to_char (out_date,  'mm') as mois_sortie,to_char (out_date,  'dd') as jour_sortie,to_char (out_date,  'HH24:MI') as heure_sortie,out_mode, entry_mode,
entry_date,to_char (entry_date,  'DD/MM/YYYY') as date_entree_char  ,to_char (out_date,  'DD/MM/YYYY') as date_sortie_char 
 from dwh_patient_stay where patient_num=$patient_num and encounter_num is not null order by entry_date asc ");
oci_execute($selval);
while ($res=oci_fetch_array($selval,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$encounter_num=$res['ENCOUNTER_NUM'];
	$mois_deb=mois_en_3lettre($res['MOIS_DEB']);
	$jour_deb=$res['JOUR_DEB'];
	$an_deb=$res['AN_DEB'];
	$heure_deb=$res['HEURE_DEB'];
	$date_entree_char=$res['DATE_ENTREE_CHAR'];
	$date_sortie_char=$res['DATE_SORTIE_CHAR'];
	$date_deb="$mois_deb $jour_deb $an_deb 00:00:00 GMT";
	
	$mois_sortie=mois_en_3lettre($res['MOIS_SORTIE']);
	$jour_sortie=$res['JOUR_SORTIE'];
	$an_sortie=$res['AN_SORTIE'];
	$heure_sortie=$res['HEURE_SORTIE'];
	$out_date="$mois_sortie $jour_sortie $an_sortie 00:00:00 GMT";

	$entry_mode=nettoyer_accent_timeline ($res['ENTRY_MODE']);
	$out_mode=nettoyer_accent_timeline ($res['OUT_MODE']);
	
	if ($an_sortie=='') {
		$out_date=$sysdate;
	}
	$last_date="$out_date";
	if ($encounter_num!='') {
		$xml_uf='';
		$requf=oci_parse($dbh,"select  distinct  unit_num,unit_code,to_char (entry_date,  'yyyy') as an_deb, to_char (entry_date,  'mm') as mois_deb,to_char (entry_date,  'dd') as jour_deb,to_char (out_date,  'yyyy') as an_sortie,to_char (out_date,  'mm') as mois_sortie,to_char (out_date,  'dd') as jour_sortie,entry_date ,to_char (entry_date,  'DD/MM/YYYY') as date_entree_uf_char  ,to_char (out_date,  'DD/MM/YYYY') as date_sortie_uf_char 
		from dwh_patient_mvt where patient_num=$patient_num and encounter_num='$encounter_num' order by entry_date asc ");
		oci_execute($requf) ;
		while ($resuf=oci_fetch_array($requf,OCI_ASSOC)) {
			$unit_num=$resuf['UNIT_NUM'];
			$unit_code=$resuf['UNIT_CODE'];
			$date_entree_uf_char=$resuf['DATE_ENTREE_UF_CHAR'];
			$date_sortie_uf_char=$resuf['DATE_SORTIE_UF_CHAR'];
			
			$mois_deb_uf=mois_en_3lettre($resuf['MOIS_DEB']);
			$jour_deb_uf=$resuf['JOUR_DEB'];
			$an_deb_uf=$resuf['AN_DEB'];
			$date_start_unit="$mois_deb_uf $jour_deb_uf $an_deb_uf 00:00:00 GMT";
			
			$mois_sortie_uf=mois_en_3lettre($resuf['MOIS_SORTIE']);
			$jour_sortie_uf=$resuf['JOUR_SORTIE'];
			$an_sortie_uf=$resuf['AN_SORTIE'];
			$date_sortie_uf="$mois_sortie_uf $jour_sortie_uf $an_sortie_uf 00:00:00 GMT";
			
			if ($an_sortie_uf=='') {
				$date_sortie_uf=$sysdate;
			}
			
			$req_uf=oci_parse($dbh,"select unit_str from dwh_thesaurus_unit where unit_num='$unit_num' ");
			oci_execute($req_uf) ;
			$r=oci_fetch_array($req_uf,OCI_ASSOC);
			$unit_str=$r['UNIT_STR'];
			
			
			
			/// liste id_doc pour surligner dans recherche ///
			$liste_class_id_doc='';
			$liste_id_doc='';
			$selvaldoc=oci_parse($dbh,"select document_num from dwh_document where  patient_num=$patient_num and encounter_num='$encounter_num' and unit_code='$unit_code' and document_origin_code !='$document_origin_code_labo'   and document_date is not null and document_date>=to_date('$date_entree_uf_char','DD/MM/YYYY') and document_date<=to_date('$date_sortie_uf_char','DD/MM/YYYY') ");
			oci_execute($selvaldoc);
			while ($resdoc=oci_fetch_array($selvaldoc,OCI_RETURN_NULLS+OCI_ASSOC)) {
				$document_num=$resdoc['DOCUMENT_NUM'];
				$liste_class_id_doc.=" class_$document_num ";
				$liste_id_doc.="$document_num,";
			}
			$liste_id_doc=substr($liste_id_doc,0,-1);
			$xml_uf.="
			<event start=\"$date_start_unit\"  classname=\"class_uf $liste_class_id_doc\" end=\"$date_sortie_uf\" isDuration=\"true\" title=\"$unit_code-$unit_str\" color=\"#285B40\">
			       $unit_code-$unit_str  ".get_translation('JS_DATE_FROM','du')." $jour_deb_uf/$mois_deb_uf/$an_deb_uf ".get_translation('JS_DATE_TO','au')." $jour_sortie_uf/$mois_sortie_uf/$an_sortie_uf &lt;br&gt;
			       &lt;br/&gt;";
			$json.="
			{
				'start':'$date_start_unit',
				'end':'$date_sortie_uf',
				'classname':'class_uf $liste_class_id_doc',
				'isDuration':'true',
				'title':'$unit_code-$unit_str',
				'color':'#285B40',
				'description':' $unit_code-$unit_str  ".get_translation('JS_DATE_FROM','du')." $jour_deb_uf/$mois_deb_uf/$an_deb_uf ".get_translation('JS_DATE_TO','au')." $jour_sortie_uf/$mois_sortie_uf/$an_sortie_uf &lt;br&gt;
				       &lt;br/&gt;";
			
			if ($liste_id_doc!='') {
				$req_liste_doc=" document_num in ($liste_id_doc) ";
				$selvaldoc=oci_parse($dbh,"select document_num, title, document_origin_code,to_char (document_date, 'DD/MM/YYYY') as tdate_document,DISPLAYED_TEXT, document_date from dwh_document where $req_liste_doc order by document_date asc ");
				oci_execute($selvaldoc);
				while ($resdoc=oci_fetch_array($selvaldoc,OCI_RETURN_NULLS+OCI_ASSOC)) {
					$document_date=$resdoc['TDATE_DOCUMENT'];
					$document_num=$resdoc['DOCUMENT_NUM'];
					$title=$resdoc['TITLE'];
					$document_origin_code=$resdoc['DOCUMENT_ORIGIN_CODE'];
					if ($resdoc['DISPLAYED_TEXT']!='') {
						$text=$resdoc['DISPLAYED_TEXT']->load();
					}
					$title=nettoyer_accent_timeline($title);
					$texte_doc="&lt;a href=\"#\" class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);return false;\"&gt; $document_date $document_origin_code $title &lt;a/&gt;  &lt;br/&gt; 
					";
					$xml_uf.=$texte_doc;
					$json.=$texte_doc;
					$tableau_document_afficher[$document_num]=$document_num;
				}
			}
			$xml_uf.="
			</event>
			";
			$json.="
			},";
			
			
			
		}
			
			
		$liste_class_id_doc='';
		$contenu_sejour='';
		$uf_avant='';
		$selvaldoc=oci_parse($dbh,"select document_num, title, document_origin_code,to_char (document_date, 'DD/MM/YYYY') as tdate_document,DISPLAYED_TEXT, document_date,unit_code,unit_num from dwh_document where patient_num=$patient_num and  encounter_num='$encounter_num' and document_origin_code !='$document_origin_code_labo'   and document_date is not null  and document_date>=to_date('$date_entree_char','DD/MM/YYYY') and document_date<=to_date('$date_sortie_char','DD/MM/YYYY')  order by document_date asc ");
		oci_execute($selvaldoc);
		while ($resdoc=oci_fetch_array($selvaldoc,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$document_date=$resdoc['TDATE_DOCUMENT'];
			$document_num=$resdoc['DOCUMENT_NUM'];
			$title=$resdoc['TITLE'];
			$document_origin_code=$resdoc['DOCUMENT_ORIGIN_CODE'];
			$unit_code=$resdoc['UNIT_CODE'];
			$num_uf_doc=$resdoc['UNIT_NUM'];
			if ($resdoc['DISPLAYED_TEXT']!='') {
				$text=$resdoc['DISPLAYED_TEXT']->load();
			}
			$icon="page-mot-blanc-icone-5880-16.png";
			$title=nettoyer_accent_timeline($title);
			$liste_class_id_doc.=" class_$document_num ";
			
			if ($num_uf_doc!='') {
				$req_uf=oci_parse($dbh,"select unit_str from dwh_thesaurus_unit where unit_num=$num_uf_doc  ");
				oci_execute($req_uf) ;
				$r=oci_fetch_array($req_uf,OCI_ASSOC);
				$unit_str=$r['UNIT_STR'];
			}
			if (($uf_avant=='' || $uf_avant!=$unit_code) && $unit_str!='') {
				$contenu_sejour.=" &lt;br&gt; &lt;strong&gt;$unit_code-$unit_str  &lt;/strong>  &lt;br&gt;";
			}
			
			$contenu_sejour.=" - &lt;a href=\"#\"  class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);return false;\"&gt; $document_date $document_origin_code $title &lt;a/&gt; &lt;br&gt;
			";
			$uf_avant=$unit_code;
			$tableau_document_afficher[$document_num]=$document_num;
		}
		
		if ($mois_sortie=='') {
			
			$json.="
			{
			'start':'$date_deb',
			'classname':'class_sejour $liste_class_id_doc',
			'isDuration':'false',
			'title':'".get_translation('JS_STAY_FROM_DATE','Sejour du')."  $jour_deb/$mois_d b/$an_deb ".get_translation('JS_TO_UNTIL_TODAY','a aujourd hui')." ',
			'color':'#990000',
			'description':'".get_translation('JS_HOSPITAL_STAY_NUMBER','NDA')." : $encounter_num &lt;br&gt;
			        ".get_translation('JS_STAY_FROM_DATE','Sejour du')." $jour_deb/$mois_deb/$an_deb ".get_translation('JS_TO_DATE','a')." ? &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_ENTRY_MODE','Mode entree')." : $entry_mode &lt;br&gt;'
			},";
			$xml.="
			<event classname=\"class_sejour $liste_class_id_doc\" start=\"$date_deb\" isDuration=\"false\" title=\"".get_translation('JS_STAY_FROM_DATE','Sejour du')."  $jour_deb/$mois_deb/$an_deb ".get_translation('JS_TO_UNTIL_TODAY','a aujourd hui')."\"  color=\"#990000\">
				".get_translation('JS_HOSPITAL_STAY_NUMBER','NDA')." : $encounter_num &lt;br&gt;
			        ".get_translation('JS_STAY_FROM_DATE','Sejour du')." $jour_deb/$mois_deb/$an_deb ".get_translation('JS_TO_DATE','a')." ? &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_ENTRY_MODE','Mode entree')." : $entry_mode &lt;br&gt;
			        
			</event>
			";
		} else {
			$json.="
			{
			'start':'$date_deb',
			'end':'$out_date',
			'classname':'class_sejour $liste_class_id_doc',
			'isDuration':'true',
			'title':'".get_translation('JS_STAY_FROM_DATE','Sejour du')."  $jour_deb/$mois_deb/$an_deb ".get_translation('JS_DATE_TO','au')." $jour_sortie/$mois_sortie/$an_sortie',
			'color':'#990000',
			'description':'".get_translation('JS_HOSPITAL_STAY_NUMBER','NDA')." : $encounter_num &lt;br&gt;
			        ".get_translation('JS_STAY_FROM_DATE','Sejour du')." $jour_deb/$mois_deb/$an_deb ".get_translation('JS_DATE_TO','au')." $jour_sortie/$mois_sortie/$an_sortie &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_ENTRY_MODE','Mode entree')." : $entry_mode &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_DISCHARGE_MODE','Mode de sortie')." : $out_mode &lt;br&gt;
			        &lt;br&gt;
			        $contenu_sejour
			},";
			$xml.="
			<event classname=\"class_sejour $liste_class_id_doc\" start=\"$date_deb\" end=\"$out_date\" isDuration=\"true\" title=\"".get_translation('JS_HOSPITAL_STAY_NUMBER','NDA')."  $jour_deb/$mois_deb/$an_deb ".get_translation('JS_DATE_TO','au')." $jour_sortie/$mois_sortie/$an_sortie\" color=\"#990000\">
				".get_translation('JS_HOSPITAL_STAY_NUMBER','NDA')." : $encounter_num &lt;br&gt;
			        ".get_translation('JS_STAY_FROM_DATE','Sejour du')." $jour_deb/$mois_deb/$an_deb ".get_translation('JS_DATE_TO','au')." $jour_sortie/$mois_sortie/$an_sortie &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_ENTRY_MODE','Mode entree')." : $entry_mode &lt;br&gt;
			        ".get_translation('JS_HOSPITAL_DISCHARGE_MODE','Mode de sortie')." : $out_mode &lt;br&gt;
			        &lt;br&gt;
			        $contenu_sejour
			</event>
			";
		}
	
	}
	$xml.="$xml_uf";
	$json.="$json_uf";
}


$liste_id_doc=implode(',',$tableau_document_afficher);
$req_id_doc='';
if ($liste_id_doc!='') {
	$req_id_doc="and document_num not in ($liste_id_doc)";
}

$selval=oci_parse($dbh,"select distinct to_char (document_date,  'yyyy') as an_deb,to_char (document_date,  'mm') as mois_deb,to_char (document_date,  'dd') as jour_deb,document_date,unit_code,to_char (document_date, 'DD/MM/YYYY') as tdate_document from dwh_document where  patient_num=$patient_num and  unit_code is not null and document_origin_code !='$document_origin_code_labo'  and document_date is not null $req_id_doc order by document_date asc ");
oci_execute($selval);
while ($res=oci_fetch_array($selval,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$unit_code=$res['UNIT_CODE'];
	$mois_deb=$res['MOIS_DEB'];
	$mois_deb_trad=mois_en_3lettre($res['MOIS_DEB']);
	$jour_deb=$res['JOUR_DEB'];
	$an_deb=$res['AN_DEB'];
	$heure_deb=$res['HEURE_DEB'];
	$document_date=$res['TDATE_DOCUMENT'];
	$date_deb="$mois_deb_trad $jour_deb $an_deb 00:00:00 GMT";
	
	$req_uf=oci_parse($dbh,"select unit_str from dwh_thesaurus_unit where unit_code='$unit_code' and date_start_unit<=to_date('$document_date','DD/MM/YYYY') and  unit_end_date>=to_date('$document_date','DD/MM/YYYY')   ");
	oci_execute($req_uf) ;
	$r=oci_fetch_array($req_uf,OCI_ASSOC);
	$unit_str=$r['UNIT_STR'];
	if ($last_date=='') {
		$last_date="$date_deb";
	}
		
		
		
	/// liste id_doc pour surligner dans recherche ///
	$liste_class_id_doc='';
	$liste_id_doc='';
	$selvaldoc=oci_parse($dbh,"select document_num from dwh_document where   patient_num=$patient_num and unit_code='$unit_code' and document_date=to_date('$jour_deb/$mois_deb/$an_deb','DD/MM/YYYY')  and document_origin_code !='$document_origin_code_labo'   and document_date is not null");
	oci_execute($selvaldoc);
	while ($resdoc=oci_fetch_array($selvaldoc,OCI_RETURN_NULLS+OCI_ASSOC)) {
		$document_num=$resdoc['DOCUMENT_NUM'];
		$liste_class_id_doc.=" class_$document_num ";
		$liste_id_doc.="$document_num,";
	}
	$liste_id_doc=substr($liste_id_doc,0,-1);
		
			
	$xml.="
	<event start=\"$date_deb\"  classname=\"class_consult $liste_class_id_doc\" isDuration=\"false\" title=\"$unit_code-$unit_str\" color=\"#285B40\">
	       $unit_code-$unit_str  ".get_translation('JS_A_DATE','du')." $jour_deb/$mois_deb/$an_deb  &lt;br&gt;
	        &lt;br/&gt;
	";
	$json.="
	{
		'start':'$date_deb',
		'classname':'class_consult $liste_class_id_doc',
		'isDuration':'false',
		'title':'$unit_code-$unit_str',
		'color':'#285B40',
		'description':'    $unit_code-$unit_str  ".get_translation('JS_A_DATE','du')." $jour_deb/$mois_deb/$an_deb  &lt;br&gt;
	        &lt;br/&gt;";
	
	if ($liste_id_doc!='') {
		$req_liste_doc="  document_num in ($liste_id_doc) ";
		$selvaldoc=oci_parse($dbh,"select document_num, title, document_origin_code,to_char (document_date, 'DD/MM/YYYY') as date_document_t,DISPLAYED_TEXT, document_date from dwh_document where $req_liste_doc order by document_date");
		oci_execute($selvaldoc);
		while ($resdoc=oci_fetch_array($selvaldoc,OCI_RETURN_NULLS+OCI_ASSOC)) {
			$date_document_t=$resdoc['DATE_DOCUMENT_T'];
			$document_num=$resdoc['DOCUMENT_NUM'];
			$title=$resdoc['TITLE'];
			$document_origin_code=$resdoc['DOCUMENT_ORIGIN_CODE'];
			if ($tableau_document_afficher[$document_num]=='') {
				if ($resdoc['DISPLAYED_TEXT']!='') {
					$text=$resdoc['DISPLAYED_TEXT']->load();
				}
				$icon="page-mot-blanc-icone-5880-16.png";
				$title=nettoyer_accent_timeline($title);
				
				$texte_doc="&lt;a href=\"#\" class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);\"&gt; $document_date $document_origin_code $title &lt;a/&gt;  &lt;br/&gt; 
				";
				$texte_doc=" &lt;a href=\"#\" class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);return false;\"&gt; $document_date $document_origin_code $title &lt;a/&gt;   &lt;br/&gt; 
				";
				$xml.=$texte_doc;
				$json.=$texte_doc;
				$tableau_document_afficher[$document_num]=$document_num;
			}
		}
	}
	$xml.="
	</event>
	";
	$json.="
	'},";
}

$selval=oci_parse($dbh,"select  
to_char (document_date,  'yyyy') as an_deb,
to_char (document_date,  'mm') as mois_deb,
to_char (document_date,  'dd') as jour_deb,
document_num, title, document_origin_code,to_char (document_date, 'DD/MM/YYYY') as tdate_document,DISPLAYED_TEXT, document_date 
 from dwh_document where patient_num=$patient_num and  unit_code is  null and document_origin_code !='$document_origin_code_labo'   and document_date is not null order by document_date asc ");
oci_execute($selval);
while ($res=oci_fetch_array($selval,OCI_RETURN_NULLS+OCI_ASSOC)) {
	$unit_code=$res['UNIT_CODE'];
	$mois_deb=$res['MOIS_DEB'];
	$mois_deb_trad=mois_en_3lettre($res['MOIS_DEB']);
	$jour_deb=$res['JOUR_DEB'];
	$an_deb=$res['AN_DEB'];
	$heure_deb=$res['HEURE_DEB'];
	$date_deb="$mois_deb_trad $jour_deb $an_deb 00:00:00 GMT";
	$document_date=$res['TDATE_DOCUMENT'];
	$document_num=$res['DOCUMENT_NUM'];
	$title=$res['TITLE'];
	$document_origin_code=$res['DOCUMENT_ORIGIN_CODE'];
	if ($tableau_document_afficher[$document_num]=='') {
		if ($res['DISPLAYED_TEXT']!='') {
			$text=$res['DISPLAYED_TEXT']->load();
		}
		
		$title=nettoyer_accent_timeline($title);
		if ($last_date=='') {
			$last_date="$date_deb";
		}
		$title=nettoyer_accent_timeline($title);
	
		$xml.="
		<event start=\"$date_deb\"  classname=\"class_consult class_$document_num\" isDuration=\"false\" title=\"$title\" color=\"#285B40\">
		      &lt;a href=\"#\" class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);return false;\"&gt; $document_date $document_origin_code $title &lt;a/&gt;   &lt;br/&gt; 
		</event>
		";
		$json.="
		{
			'start':'$date_deb',
			'classname':'class_consult class_$document_num',
			'isDuration':'false',
			'title':'$title',
			'color':'#285B40',
			'description':'&lt;a href=\"#\" class=\"class_doc class_doc_$document_num\" onclick=\"ouvrir_document_timeline($document_num);return false;\"&gt; $document_date $document_origin_code $title &lt;a/&gt;   &lt;br/&gt;'
		},
		";
	}
	
}

if ($death_dateclair=='') {
	$xml.="
	<event start=\"$birth_date\" end=\"$sysdate\" classname=\"class_patient\" isDuration=\"true\" title=\"".get_translation('JS_PATIENT_LIFE','Vie du patient')."\" >
	         ".get_translation('JS_PATIENT_BORN_DATE','Patient(e) ne(e) le')." $datenaisclair
	</event>
	</data>
	";
	
	$json.="
	{
		'start':'$birth_date',
		'end':'$sysdate',
		'classname':'class_patient',
		'isDuration':'true',
		'title': '".get_translation('JS_PATIENT_LIFE','Vie du patient')."',
		'description':'".get_translation('JS_PATIENT_BORN_DATE','Patient ne(e) le')."  $datenaisclair'
	}]}
	";
} else {

	$xml.="
	<event start=\"$birth_date\" end=\"$death_date\" classname=\"class_patient\" isDuration=\"true\" title=\"".get_translation('JS_PATIENT_LIFE','Vie du patient')."\" >
	        ".get_translation('JS_PATIENT_BORN_DATE','Patient ne(e) le')." $datenaisclair, ".get_translation('JS_DECEASED_AT','decede le')."  $death_dateclair
	</event>
	</data>
	";
	
	$json.="
	{
		'start':'$birth_date',
		'end':'$death_date',
		'classname':'class_patient',
		'isDuration':'true',
		'title':'".get_translation('JS_PATIENT_LIFE','Vie du patient')."',
		'description':' ".get_translation('JS_PATIENT_BORN_DATE','Patient ne(e) le')." $datenaisclair, ".get_translation('JS_DECEASED_AT','decede le')." $death_dateclair'
	}]}
	";
}

$monfichier = fopen("timeline/xml/".$patient_num."_timeline.xml", 'w+');
fputs($monfichier,"$xml");
fclose($monfichier);
?>