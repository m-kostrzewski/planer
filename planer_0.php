<?php
defined("_VALID_ACCESS") || die('Direct access forbidden');
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class planer extends Module { 


    public function body(){

		Base_ThemeCommon::install_default_theme($this->get_type());
        $theme = $this->init_module('Base/Theme');
        // --------------------------DEFAULT .TPL -------------------------
        $wordPNG = '<img src="data/Base_Theme/templates/default/planer/word.png" height="16" width="16" />';

        if(isset($_REQUEST['__jump_to_RB_table'])){    
            $rs = new RBO_RecordsetAccessor($_REQUEST['__jump_to_RB_table']);
            $rb = $rs->create_rb_module ( $this );
            $this->display_module ( $rb);
        }    
        if(!isset($_REQUEST['mode']) && !isset($_REQUEST['__jump_to_RB_table']) ){

            $theme->assign("css", Base_ThemeCommon::get_template_dir());
            $rbo = new RBO_RecordsetAccessor("Sales_plan");

            $companes = new RBO_RecordsetAccessor("company");
            $days = array();
            //array('change_status' => '2018-07-19','status'=> '1'))
            if(isset($_REQUEST["change_status"])){
                $_date = $_REQUEST["change_status"];
                $status = $_REQUEST["status"];
                $records = Utils_RecordBrowserCommon::get_records('Sales_plan', array("date"=> $_date),array(),array());
                foreach($records as $record_){
                Utils_RecordBrowserCommon::update_record('Sales_plan', $record_['id'], array('difficulty_level' => $status),$all_fields=false, 
                null, $dont_notify=false);

            }
            }
            if(!isset($_REQUEST['year']) && !isset($_SESSION['year'])){
                $year = date("Y");
                $_SESSION['year'] = $year;
            }
            elseif(isset($_REQUEST['year'])){
                $year= $_REQUEST['year'];
                $_SESSION['year'] = $year;
            }
            elseif(isset($_SESSION['year']) && !isset($_REQUEST['year'])){
                $year= $_SESSION['year'];
            }
            $date = new PickDate($year);
            if(!isset($_REQUEST['week_number']) && !isset($_SESSION['week'])){
                $today = date("Y-m-d");
                //$today = date("Y-m-d",strtotime("2019-01-04"));
                $time = strtotime($today);
                $day_in_week = date("N", $time);
                $week = date("W", $time);
                if($day_in_week > 1 && date("n", $time) == 1 && $week == 1){
                    $time -= ($day_in_week-1)  * 60*60*24;
                    $year = date("Y", $time);
                    $date->update_year($year);
                }
                $today = date("Y-m-d",$time);
                $week_num = $date->get_week_number($today);  
                $_SESSION['week'] = $week_num;     
            }
            elseif(isset($_REQUEST['week_number'])){
                $week_num= $_REQUEST['week_number'];   
                $_SESSION['week'] = $week_num;  
            }
            elseif(isset($_SESSION['week']) && !isset($_REQUEST['week_number'])){
                $week_num= $_SESSION['week'];  
            }
            if(isset ($_REQUEST["delete_record"])){
                $delete_record = $_REQUEST['delete_record'];
                $rbo->delete_record($delete_record);
            }
            if(isset($_REQUEST['copy'])){
                if(Addons::can_copy($week_num,$year)){
                    $sales = new RBO_RecordsetAccessor("Sales_plan");
                    if($week_num - 1 < 2){
                        $from = 53;
                        $y = $year - 1;
                    }else{
                        $from = $week_num - 1;
                        $y = $year;
                    }
                    if(strlen($from) == 1){
                        $from = "0".$from;
                    }

                    $s = date("$y-m-d", strtotime($y.'W'.$from));
                    $start_date = $s;
                    $end_date = $date->add_days($s,4);
                    $records = $sales->get_records(array('>=date' => $start_date, '<=date' => $end_date));
                    foreach($records as $record){
                        $new_record = array("company_name" => $record['company_name'] , "amount" => $record['amount'] ,
                        "date" => $date->add_days($record["date"],7) ,"description_trader" => $record["description_trader"] ,
                        "description_manager" => $record["description_manager"], "difficulty_level" => $record["difficulty_level"]);
                        $now = date("$year-m-d H:i:s");
                        $new = $sales->new_record($new_record);
                        $new->created_by = Acl::get_user();
                        $new->created_on = $now;  
                        $id = $user->id;
                        $new->save();    
                    }
                    Addons::copied($week_num,$year);
                }
            }
            //test 
            //print($week_num." > ".$year);
            //sortowanie wg nazw firm
            function sortByCompanyName($array){
                $list_of_company = [];
                $new_list = [];
                foreach($array as $record){
                        $list_of_company[] = strip_tags($record['company_name']);
                }
                $records = $array;
                sort($list_of_company);
                foreach($list_of_company as $alfabetic){
                    foreach($records as $record){
                        print(strip_tags($record['company_name']).":".$alfabetic."<BR>");
                        if(strip_tags($record['company_name']) == $alfabetic){
                                $new_list[] = $record;
                                unset($records[$record]);
                                break;
                        }
                    }
                    print(count($list_of_company));
                }
                return $new_list;
            }
            //nowy record
            $x = 0;
            Base_ActionBarCommon::add(
                'add',
                __('New'), 
                Utils_RecordBrowserCommon::create_new_record_href('Sales_plan', $this->custom_defaults),
                null,
                $x
            );
            $x++;
            //poprzedni tydzien
            if($week_num-1 < 2 ) {
                $w = 53;
                $y = $year - 1;
            }
            else {
                $w = $week_num -1;
                $y = $year;
            }
            Base_ActionBarCommon::add(
                Base_ThemeCommon::get_template_file($this->get_type(), 'prev.png'),
               "Poprzedni tydzień",
                $this->create_href ( array ('week_number' => $w, 'year'=>$y)),
                null,
                $x
            );
            $x++;
            // 7 tygodni do wyboru
            for($i = $week_num - 3 ; $i < $week_num + 4;$i++){

                if($i > 53) {
                    $week = $i - 53;
                    if($week != 1) {
                        $week_print = $week;
                        if ($week_num == $i) {
                            $icon = 'cal2.png';
                        } else {
                            $icon = 'cal.png';
                        }
                        Base_ActionBarCommon::add(
                            Base_ThemeCommon::get_template_file($this->get_type(), $icon),
                            "Tydzień - " . $week_print,
                            $this->create_href(array('week_number' => $week, 'year' => $year + 1)),
                            null,
                            $x
                        );
                        $x = $x + 1;
                    }
                    }
                elseif ($i < 1){
                    $week = 53 + $i;
                    if($week != 1) {
                        $txt = $week;
                        if($txt == 53){
                            $txt = "1";
                        }
                        if ($week_num == $i) {
                            $icon = 'cal2.png';
                        } else {
                            $icon = 'cal.png';
                        }
                        Base_ActionBarCommon::add(
                            Base_ThemeCommon::get_template_file($this->get_type(), $icon),
                            "Tydzień - " . $txt,
                            $this->create_href(array('week_number' => $week, 'year' => $year - 1)),
                            null,
                            $x
                        );
                        $x = $x + 1;
                    }
                }
                else if($i != 1){
                    $txt = $i;
                    if($txt == 53){
                        $txt = "1";
                    }
                    if($week_num == $i){ $icon = 'cal2.png'; }else{ $icon = 'cal.png'; }
                        Base_ActionBarCommon::add(
                            Base_ThemeCommon::get_template_file($this->get_type(), $icon),
                            "Tydzień - ".$txt,
                            $this->create_href ( array ('week_number' => $i, 'year'=> $year)),
                            null,
                            $x
                        );
                        $x = $x +1;
                    }
            }
            //nastepny tydzien
            if($week_num + 1 > 53) {
                $w = 2;
                $y = $year +1;
            }else{
                $w = $week_num + 1;
                $y = $year;
            }

            Base_ActionBarCommon::add(
                Base_ThemeCommon::get_template_file($this->get_type(), 'next.png'),
                "Następny tydzień",
                $this->create_href ( array ('week_number' => $w, 'year'=>$y)),
                null,
                $x
            );
            $x++;

            if(Addons::can_copy($week_num,$year)){
                Base_ActionBarCommon::add('add', 
                        __('Copy from last week'), 
                        $this->create_href ( array ('copy' => TRUE,'week_number' => $week_num ,'year'=>$year )),
                        null,
                        $x
                    );
                $x++;
            }
            $select_options = "<li><a ".$this->create_href(array('week_number' => $date->get_week_number(date("Y-m-d")), 'year'=> date("Y")))."> Wróć do bieżącego tygodnia </a></li>";
            for($i = 1; $i<=52;$i++){
                $select_options .= "<li><a ".$this->create_href(array('week_number' => $i, 'year'=> date("Y")))."> Tydzień - ".$i." </a></li>";
            }
            
            $select = "<ul class='drops'>
                            <li>
                                <a href='#'>Wybierz tydzień </a> <img src='data/Base_Theme/templates/default/planer/drop.png' width=25 height=25 />
                                <ul>".$select_options."</ul>
                            </li>
                        </ul>";
            // zamowione 
			$company_field = "company"; ///company company_name
            $all_zam = 0;
            $user = new RBO_RecordsetAccessor('contact');
            $days_zam = array();
            $loginContact = CRM_ContactsCommon::get_contact_by_user_id(Base_AclCommon::get_user ());
            $is_manager = $loginContact['access']['manager'];
            $day = $date->monday_of_week($week_num);
            $pon = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
            //$pon = Rbo_Futures::set_related_fields($pon, 'company_name');

            foreach($pon as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $days_zam[1] += $p["amount"];
                $all_zam += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager  || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                        "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{$infobox =
                        "---";}
                
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $deli = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $deli;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
                
            }
            //$pon = sortByCompanyName($pon);
            $day = $date->add_days($date->monday_of_week($week_num), 1);
            $wt = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
           // $wt = Rbo_Futures::set_related_fields($wt, 'company_name');
            foreach($wt as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $all_zam += $p["amount"];
                $days_zam[2] += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                        "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }
                    else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
            }
        // print(count($pon));
        // $wt = sortByCompanyName($wt);
            $day = $date->add_days($date->monday_of_week($week_num), 2);
            $sr = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
        //    $sr = Rbo_Futures::set_related_fields($sr, 'company_name');
            foreach($sr as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $days_zam[3] += $p["amount"];
                $all_zam += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                        "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
            }
        //  $sr = sortByCompanyName($sr);
            $day = $date->add_days($date->monday_of_week($week_num), 3);
            $czw = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
         //   $czw = Rbo_Futures::set_related_fields($czw, 'company_name');
            foreach($czw as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $days_zam[4] += $p["amount"];
                $all_zam += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                        "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
            }
        //   $czw = sortByCompanyName($czw);
            $day = $date->add_days($date->monday_of_week($week_num), 4);
            $pt = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
         //   $pt = Rbo_Futures::set_related_fields($pt, 'company_name');
            foreach($pt as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $all_zam += $p["amount"];
                $days_zam[5] += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                        "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                    }
            }
            $day = $date->add_days($date->monday_of_week($week_num), 5);
            $sob = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
         //   $sob = Rbo_Futures::set_related_fields($sob, 'company_name');
            foreach($sob as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $all_zam += $p["amount"];
                $days_zam[6] += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                            "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }
                    else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
            }
            $day = $date->add_days($date->monday_of_week($week_num), 6);
            $nd = $rbo->get_records(array('date' => $day),array(),array('company_name' => "ASC"));
           // $nd = Rbo_Futures::set_related_fields($nd, 'company_name');
            foreach($nd as $p){
                $href = 'href="modules/planer/word.php?'.http_build_query(array('date'=> $day , 'company' => $p['company_name'] , 'cid'=>CID)).'"';
                $p['word'] = " <a ".$href ." > ".$wordPNG. "</a>" ;
                $p['company_name'] =  $p->get_val('company_name',false);
                $all_zam += $p["amount"];
                $days_zam[7] += $p["amount"];
                $p['amount'] = $p->record_link($p['amount'],$nolink = false,$action = 'view');
                if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                    if(strlen($p['Description trader']) > 0 || strlen($p['Description Manager']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader'].
                            "</div>", "Manager: " => "<div class='custom_info'>".$p['Description Manager']."</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }
                    else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["edit"] = $p->record_link('<img class="action_button" src="data/Base_Theme/templates/default/Utils/GenericBrowser/edit.png" border="0" alt="Edytuj">',$nolink=false,'edit');
                    $del = $this->create_href(array("delete_record" => $p['id']));
                    $del = "<a $del> <img border='0' src='data/Base_Theme/templates/default/Utils/Calendar/delete.png' alt='Usuń' /></a>";
                    $p["delete"] = $del;
                }
                else{
                    if(strlen($p['Description trader']) > 0){
                        $ar = array("Handlowiec: " => "<div class='custom_info'>".$p['Description trader']. "</div>");
                        $infobox = Utils_TooltipCommon::format_info_tooltip($ar);
                        $infobox = Utils_TooltipCommon::create("Informacje dodatkowe",$infobox,$help=true, $max_width=300);
                    }else{
                        $infobox = "---";
                    }
                    $p['notka'] = $infobox;
                    $p["delete"] = '';
                    $p["edit"] = '';
                }
            }
            $all_bought_week =0;
            $all_transported_week = 0;
        //  $pt = sortByCompanyName($pt);
            //potrzeba wstawić prawidłową nazwe tabeli
            $bought = new RBO_RecordsetAccessor("custom_agrohandel_purchase_plans"); //EDIT
            $pon_bought = $bought->get_records(array('planed_purchase_date' => $date->monday_of_week($week_num),'~status' => "%purchased%"),
                                            array("Company" => "ASC"));
            $wt_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 1),'~status' => "%purchased%"),
                                            array("Company" => "ASC"));
            $sr_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 2),'~status' => "%purchased%"),
                                            array("Company" => "ASC"));
            $czw_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 3),'~status' => "%purchased%"),
                                            array("Company" => "ASC"));
            $pt_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 4),'~status' => "%purchased%"),
                                            array("Company" => "ASC"));
            $sob_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 5),'~status' => "%purchased%"),
                array("Company" => "ASC"));
            $nd_bought = $bought->get_records(array('planed_purchase_date' => $date->add_days($date->monday_of_week($week_num), 6),'~status' => "%purchased%"),
                array("Company" => "ASC"));
            // kupione
            $pon_companes = array();
            $wt_companes = array();
            $sr_companes = array();
            $czw_companes = array();
            $pt_companes = array();
            $sob_companes = array();
            $nd_companes = array();
            foreach($pon as $pone){
                array_push($pon_companes , $pone['company_name']);
            }
            foreach($wt as $pone){
                array_push($wt_companes , $pone['company_name']);
            }
            foreach($sr as $pone){
                array_push($sr_companes , $pone['company_name']);
            }
            foreach($czw as $pone){
                array_push($czw_companes , $pone['company_name']);
            }
            foreach($pt as $pone){
                array_push($pt_companes , $pone['company_name']);
            }
            foreach($sob as $pone){
                array_push($sob_companes , $pone['company_name']);
            }
            foreach($nd as $pone){
                array_push($nd_companes , $pone['company_name']);
            }
            $pon_companes = array_count_values($pon_companes);
            $wt_companes = array_count_values($wt_companes);
            $sr_companes = array_count_values($sr_companes);
            $czw_companes = array_count_values($czw_companes);
            $pt_companes = array_count_values($pt_companes);
            $sob_companes = array_count_values($sob_companes);
            $nd_companes = array_count_values($nd_companes);

            $i  = 1;
            $indexer = array();
            foreach($pon_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($wt_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($sr_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($czw_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($pt_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($sob_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
            foreach($nd_companes as $com){
                $indexer[$i] = $com;
                $i++;
            }
		
           //dostarczone && zaladowane
            //potrzena tabela z Raport z rozladunku
            $transported = new RBO_RecordsetAccessor("custom_agrohandel_transporty"); //custom_agrohandel_transporty Transport
            $trans_pon = array();
            $trans_wt = array();
            $trans_sr = array();
            $trans_czw = array();
            $trans_pt = array();
            $trans_sob = array();
            $trans_nd = array();

            $transports_sum_of_day = array(1=>0,2=>0,3=>0,4=>0,5=>0, 6=>0,7=>0);
            $transports = [];
            $amount = "iloscrozl"; //iloscrozl amount
			$week_loads = array();
			$all_loaded_week = 0;
			$loaded_pon = array();
            $loaded_wt = array();
            $loaded_sr = array();
            $loaded_czw = array();
            $loaded_pt = array();
            $loaded_sob = array();
            $loaded_nd = array();

			$reload_pon = array();
			$reload_wt = array();
			$reload_sr = array();
			$reload_czw = array();
			$reload_pt = array();
            $reload_sob = array();
            $reload_nd = array();

			$reload_sum = 0;
			
			$loaded_field_name = 'sztukzal';
			
			$loadings_sum_of_day = array(1=>0,2=>0,3=>0,4=>0,5=> 0,6=>0,7=>0);
          
            $t_pon = $transported->get_records(array('date' => $date->monday_of_week($week_num)),array(),array($company_field => "ASC"));
            foreach($t_pon as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_pon[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t[$company_field]);
                if($is_ubojnia['group']['baza_tr']){
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$reload_pon[$t->id]['name'] = $x;
						$reload_pon[$t->id]['count'] += $purchase_plan[$loaded_field_name];
						$reload_sum += $purchase_plan[$loaded_field_name];
						$days_load[1] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[1] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];			
					}		
				}else{
					$all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[1] += $t[$amount];
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$loaded_pon[$x] += $purchase_plan[$loaded_field_name];
						$days_load[1] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[1] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];
					}	
                }
            }
            foreach($t_pon as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_pon[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_pon[$x]."</a>";
				
            }
            $t_wt = $transported->get_records(array('date' =>$date->add_days($date->monday_of_week($week_num), 1)),array(),array($company_field => "ASC"));
            foreach($t_wt as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_wt[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$reload_wt[$t->id]['name'] = $x;
						$reload_wt[$t->id]['count'] += $purchase_plan[$loaded_field_name];
						$reload_sum += $purchase_plan[$loaded_field_name];
						$days_load[2] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[2] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];			
					}		
				}else{
					$all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[2] += $t[$amount];
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$loaded_wt[$x] += $purchase_plan[$loaded_field_name];
						$days_load[2] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[2] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];
					}	
                }
            }
            foreach($t_wt as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_wt[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_wt[$x]."</a>";
            }
            $t_sr = $transported->get_records(array('date' => $date->add_days($date->monday_of_week($week_num), 2)),array(),array($company_field => "ASC"));
            foreach($t_sr as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_sr[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$reload_sr[$t->id]['name'] = $x;
						$reload_sr[$t->id]['count'] += $purchase_plan[$loaded_field_name];
						$reload_sum += $purchase_plan[$loaded_field_name];
						$days_load[3] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[3] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];			
					}		
				}else{
					$all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[3] += $t[$amount];
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$loaded_sr[$x] += $purchase_plan[$loaded_field_name];
						$days_load[3] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[3] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];
					}	
                }
            }
            foreach($t_sr as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_sr[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_sr[$x]."</a>";
            }
            $t_czw = $transported->get_records(array('date' =>$date->add_days($date->monday_of_week($week_num), 3)),array(),array($company_field => "ASC"));
            foreach($t_czw as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_czw[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$reload_czw[$t->id]['name'] = $x;
						$reload_czw[$t->id]['count'] += $purchase_plan[$loaded_field_name];
						$reload_sum += $purchase_plan[$loaded_field_name];
						$days_load[4] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[4] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];			
					}		
				}else{
					$all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[4] += $t[$amount];
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$loaded_czw[$x] += $purchase_plan[$loaded_field_name];
						$days_load[4] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[4] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];
					}	
                }
            }
            foreach($t_czw as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_czw[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_czw[$x]."</a>";
            }
            $t_pt = $transported->get_records(array('date' => $date->add_days($date->monday_of_week($week_num), 4)),array(),array($company_field => "ASC"));
            foreach($t_pt as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_pt[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$reload_pt[$t->id]['name'] = $x;
						$reload_pt[$t->id]['count'] += $purchase_plan[$loaded_field_name];
						$reload_sum += $purchase_plan[$loaded_field_name];
						$days_load[5] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[5] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];			
					}		
				}else{
					$all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[5] += $t[$amount];
					$once = $t["zakupy"];
					foreach($once as $one){
						$purchase_plan  = $bought->get_record($one);//purchase_plan_
						$loaded_pt[$x] += $purchase_plan[$loaded_field_name];
						$days_load[5] +=  $purchase_plan[$loaded_field_name];
						$all_loaded_week +=  $purchase_plan[$loaded_field_name];
						$loadings_sum_of_day[5] += $purchase_plan[$loaded_field_name];
						$week_loads[$x] += $purchase_plan[$loaded_field_name];
					}	
                }
            }
            foreach($t_pt as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_pt[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_pt[$x]."</a>";
            }
            $t_sob = $transported->get_records(array('date' => $date->add_days($date->monday_of_week($week_num), 5)),array(),array($company_field => "ASC"));
            foreach($t_sob as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_sr[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
                    $once = $t["zakupy"];
                    foreach($once as $one){
                        $purchase_plan  = $bought->get_record($one);//purchase_plan_
                        $reload_sr[$t->id]['name'] = $x;
                        $reload_sr[$t->id]['count'] += $purchase_plan[$loaded_field_name];
                        $reload_sum += $purchase_plan[$loaded_field_name];
                        $days_load[6] +=  $purchase_plan[$loaded_field_name];
                        $all_loaded_week +=  $purchase_plan[$loaded_field_name];
                        $loadings_sum_of_day[6] += $purchase_plan[$loaded_field_name];
                        $week_loads[$x] += $purchase_plan[$loaded_field_name];
                    }
                }else{
                    $all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[6] += $t[$amount];
                    $once = $t["zakupy"];
                    foreach($once as $one){
                        $purchase_plan  = $bought->get_record($one);//purchase_plan_
                        $loaded_sr[$x] += $purchase_plan[$loaded_field_name];
                        $days_load[6] +=  $purchase_plan[$loaded_field_name];
                        $all_loaded_week +=  $purchase_plan[$loaded_field_name];
                        $loadings_sum_of_day[6] += $purchase_plan[$loaded_field_name];
                        $week_loads[$x] += $purchase_plan[$loaded_field_name];
                    }
                }
            }
            foreach($t_sob as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_sob[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_sob[$x]."</a>";
            }
            $t_nd = $transported->get_records(array('date' => $date->add_days($date->monday_of_week($week_num), 6)),array(),array($company_field => "ASC"));
            foreach($t_nd as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_sr[$x] += $t[$amount];
                $is_ubojnia = $companes->get_record($t['company']);
                if($is_ubojnia['group']['baza_tr']){
                    $once = $t["zakupy"];
                    foreach($once as $one){
                        $purchase_plan  = $bought->get_record($one);//purchase_plan_
                        $reload_sr[$t->id]['name'] = $x;
                        $reload_sr[$t->id]['count'] += $purchase_plan[$loaded_field_name];
                        $reload_sum += $purchase_plan[$loaded_field_name];
                        $days_load[7] +=  $purchase_plan[$loaded_field_name];
                        $all_loaded_week +=  $purchase_plan[$loaded_field_name];
                        $loadings_sum_of_day[7] += $purchase_plan[$loaded_field_name];
                        $week_loads[$x] += $purchase_plan[$loaded_field_name];
                    }
                }else{
                    $all_transported_week +=  $t[$amount];
                    $transports_sum_of_day[3] += $t[$amount];
                    $once = $t["zakupy"];
                    foreach($once as $one){
                        $purchase_plan  = $bought->get_record($one);//purchase_plan_
                        $loaded_sr[$x] += $purchase_plan[$loaded_field_name];
                        $days_load[7] +=  $purchase_plan[$loaded_field_name];
                        $all_loaded_week +=  $purchase_plan[$loaded_field_name];
                        $loadings_sum_of_day[7] += $purchase_plan[$loaded_field_name];
                        $week_loads[$x] += $purchase_plan[$loaded_field_name];
                    }
                }
            }
            foreach($t_nd as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $trans_nd[$x] = "<a style='color:#0a07bd;' ".$this->create_href(array('mode' => 'firma' ,'date' => $t['date'], 'firma_id'=> $t[$company_field])).">".$trans_nd[$x]."</a>";
            }

            $week_trans = array();
            $week_transported = $transported->get_records(array('>=date' => $date->add_days($date->monday_of_week($week_num),0),
            '<=date' => $date->add_days($date->monday_of_week($week_num), 6)),array(),array($company_field => "ASC"));
            foreach($week_transported as $t){
                $x = $t->get_val($company_field,$nolink = TRUE);
                $week_trans[$x] += $t[$amount];
            }
			
            $transports[1] = $trans_pon;
            $transports[2] = $trans_wt;
            $transports[3] = $trans_sr;
            $transports[4] = $trans_czw;
            $transports[5] = $trans_pt;
            $transports[6] = $trans_sob;
            $transports[7] = $trans_nd;
			
			$loadings = [];
			$loadings[1] = $loaded_pon;
			$loadings[2] = $loaded_wt;
			$loadings[3] = $loaded_sr;
			$loadings[4] = $loaded_czw;
			$loadings[5] = $loaded_pt;
            $loadings[6] = $loaded_sob;
            $loadings[7] = $loaded_nd;
			
			$reloads  = [];
			$reloads[1] = $reload_pon;
			$reloads[2] = $reload_wt;
			$reloads[3] = $reload_sr;
			$reloads[4] = $reload_czw;
			$reloads[5] = $reload_pt;
            $reloads[6] = $reload_sob;
            $reloads[7] = $reload_nd;

			$theme->assign('reload_sum',$reload_sum);
			$theme->assign('reloads',$reloads);
			$theme->assign('load',$loadings);
            $theme->assign('trans',$transports);
            $starter = $indexer[0];
            $theme->assign('all_zam',$all_zam);
            $theme->assign('starter',$starter);
            $theme->assign('indexer',$indexer);
            $theme->assign('select',$select);
            //purchased or Kupione => Status   Amount   Company  planed_purchase_date  Company
            $amount_sum = array(1=>$this->sum_records($pon_bought,'Amount'),
            2=>$this->sum_records($wt_bought,'Amount'),3=>$this->sum_records($sr_bought,'Amount'),
            4=>$this->sum_records($czw_bought,'Amount'),
            5=>$this->sum_records($pt_bought,'Amount'),
            6=>$this->sum_records($sob_bought,'Amount'),
            7=>$this->sum_records($nd_bought,'Amount'));
            foreach($amount_sum as $sum){
                $all_bought_week += $sum;
            }

            for($i = 1;$i<8;$i++){
                $amount_sum[$i] = "<a ". Base_BoxCommon::create_href('Custom/Agrohandel/Transporty','Custom/Agrohandel/Transporty', null, array(), array(), array('day'=> $date->add_days($date->monday_of_week($week_num),($i-1)))).">".$amount_sum[$i]."</a>";
            }
            array_push($days,$pon);
            array_push($days,$wt);
            array_push($days,$sr);
            array_push($days,$czw);
            array_push($days,$pt);
            array_push($days,$sob);
            array_push($days,$nd);

            //dni tygodnia
            $days_text = array(
                1=>"PONIEDZIAŁEK",
                2=>"WTOREK",
                3=>"ŚRODA",
                4=>"CZWARTEK",
                5=>"PIĄTEK",
                6=>"SOBOTA",
                7=>"NIEDZIELA",

            );
            $days_link = array(
                1=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 0))),
                2=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 1))),
                3=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 2))),
                4=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 3))),
                5=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 4))),
                6=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 5))),
                7=>$this->create_href(array('mode' => 'day' ,'date' => $date->add_days($date->monday_of_week($week_num), 6)))
            );
            $week_number_link = $this->create_href(array('mode' => 'week' ,'date' => $week_num));
            $theme->assign('week_link',$week_number_link);
            $theme->assign('days_link',$days_link);
            if($is_manager || Base_AclCommon::i_am_sa() == "1" || Base_AclCommon::i_am_admin() == "1" ){
                for($i = 0; $i<7;$i++){
                    $sel_opt = "";
                    $sel_opt .= "<a style='position:relative;z-index:5;' ".$this->create_href(array('change_status' => $date->add_days($date->monday_of_week($week_num), $i),'status'=> '2'))."><img src='data/Base_Theme/templates/default/planer/good.png'  width=15 height=15 /></a>";
                    $sel_opt .= "<a style='position:relative;z-index:5;' ".$this->create_href(array('change_status' => $date->add_days($date->monday_of_week($week_num), $i),'status'=> '1'))."><img src='data/Base_Theme/templates/default/planer/normal.png'  width=15 height=15 /></a>";
                    $sel_opt .= "<a style='position:relative;z-index:5;' ".$this->create_href(array('change_status' => $date->add_days($date->monday_of_week($week_num), $i),'status'=> '3'))."><img src='data/Base_Theme/templates/default/planer/bad.png'  width=15 height=15 /></a>";
                    $sel = "<div style='position:relative;text-align:center;'><br>Zmień status:<br>".$sel_opt."</div>";
                    $x = $i;
                    $x++;
                    $days_text[$x.$x] = $sel;
                }
            }
            $sumary_week = $rbo->get_records(array('>=date' => $date->monday_of_week($week_num), 
            '<=date' => $date->add_days($date->monday_of_week($week_num), 6)),
            array(),array());
            $mach_week_with_tr = $rbo->get_records(array('>=date' => $date->monday_of_week($week_num), 
            '<=date' => $date->add_days($date->monday_of_week($week_num), 6)),
            array(),array());
            $mach_tr_with_week = $transported->get_records(array('>=date' => $date->monday_of_week($week_num), 
            '<=date' => $date->add_days($date->monday_of_week($week_num), 6)),array(),array());
            $missing_pon = array();
            $missing_wt = array();
            $missing_sr = array();
            $missing_czw = array();
            $missing_pt = array();
            $missing_sob = array();
            $missing_nd = array();
            $missing_all = array();
            if(count($mach_week_with_tr) != count($mach_tr_with_week)){
                foreach($mach_tr_with_week as $trans){
                    $exist = false;
                    foreach($mach_week_with_tr as $plan){
                        if($trans[$company_field] == $plan['company_name']){
                            $exist = true;
                            break;
                        }
                    }
                    if($exist == false){
                        $ubojnia = true;
                        $_transport = $companes->get_records(array('id' => $trans[$company_field], 'group' => 'baza_tr'),array(),array());
                        if($_transport != null){
                            $ubojnia = false;
                        }
                        if($ubojnia == true){
                            $amount = 0;
                            $once = $trans->to_array();
                            $once = $once["zakupy"];
                            foreach($once as $one){
                                $value  = $bought->get_record($one);//purchase_plan_
                                $amount += $value['amount'];
                                $all_transported_week += $value['iloscrozl'];
                            }
                            $trans['company'] =  $trans->get_val('company');
                            $trans['amm'] = $amount; 
                            $missing_all[] = $trans;
                            $dayofweek = date('w', strtotime($trans['date']));  
                            if($dayofweek == 1){ $missing_pon[] = $trans;}
                            else if($dayofweek == 2){ $missing_wt[] = $trans;}
                            else if($dayofweek == 3){ $missing_sr[] = $trans;}
                            else if($dayofweek == 4){ $missing_czw[] = $trans;}
                            else if($dayofweek == 5){ $missing_pt[] = $trans;}
                            else if($dayofweek == 6){ $missing_sob[] = $trans;}
                            else if($dayofweek == 7){ $missing_nd[] = $trans;}
                        }               
                    }
                }
            }       
            $week_amount_sum = 0;   
            for($i=0;$i<=6;$i++){
                $week_bought = $transported->get_records(array('date' =>$date->add_days($date->monday_of_week($week_num), $i)),array(),array());
                foreach($week_bought as $day){
                    $once = $day->to_array();
                    $once = $once["zakupy"];
                    foreach($once as $one){
                        $value  = $bought->get_record($one);
                        $week_amount_sum += $value['amount'];
                    }
                }                 
            } 
            $missing = array();
            $missing[1] = $missing_pon;
            $missing[2] = $missing_wt;
            $missing[3] = $missing_sr;
            $missing[4] = $missing_czw;
            $missing[5] = $missing_pt;
            $missing[6] = $missing_sob;
            $missing[7] = $missing_nd;
            // missing[0] = missing_pon -> records
            $sum_week = array();
            foreach($sumary_week as $sum){
                try{
                $value = $sum_week[$sum->get_val("company_name",$nolink=true)]["val"];
                }catch(Exception $e){$value = 0;}
                $value = intval($value) + intval($sum['amount']); 
                $sum_week[$sum->get_val("company_name",$nolink=true)] = array("val" => $value,
                                                                            "name" =>$sum->get_val("company_name",$nolink=true));
            }

            $wn = $week_num;
            if($wn == 53){
                $wn = 1;
            }

            $thisWeek = $date->add_days($date->monday_of_week($wn), - 5);
            $prevWeek = $date->add_days($date->monday_of_week($wn), 2);

            $rbo = new RBO_RecordsetAccessor("currency_history");
            $prevWeekRecords = $rbo->get_records(array('date' => $prevWeek, '!euro' => '', '!zmp' => ''), array(), array());
            foreach($prevWeekRecords as $p){$prevWeekRecords = $p;}
            $thisWeekRecords = $rbo->get_records(array('date' => $thisWeek, '!euro' => '', '!zmp' => ''), array(), array());
            foreach($thisWeekRecords as $t){$thisWeekRecords = $t;}
            if( $prevWeekRecords == null){
                planerCommon::downloadDay($prevWeek);
            }
            else if($prevWeekRecords['euro'] == 0 || $prevWeekRecords['zmp'] == 0 ) {
                planerCommon::downloadDay($prevWeek);
            }

            if( $thisWeekRecords == null){
                planerCommon::downloadDay($thisWeek);
            }
            else if($thisWeekRecords['euro'] == 0 || $thisWeekRecords['zmp'] == 0 ) {
                planerCommon::downloadDay($thisWeek);
            }
    
            $prevWeekRecords = $rbo->get_records(array('date' => $prevWeek), array(), array());
            $thisWeekRecords = $rbo->get_records(array('date' => $thisWeek), array(), array());
            foreach($prevWeekRecords as $p){$prevWeekRecords = $p;}
            foreach($thisWeekRecords as $t){$thisWeekRecords = $t;}
            $prevWeekRecords['price'] = $prevWeekRecords['euro'] * $prevWeekRecords['zmp'];
            $thisWeekRecords['price'] = $thisWeekRecords['euro'] * $thisWeekRecords['zmp'];

            $prevWeekRecords['price'] = str_replace(".", ",", round($prevWeekRecords['price'],2));
            $prevWeekRecords['euro'] = str_replace(".", ",", $prevWeekRecords['euro']);
            $prevWeekRecords['zmp'] = str_replace(".", ",", $prevWeekRecords['zmp']);
            $prevWeekRecords['week'] = $wn - 1;

            $thisWeekRecords['price'] = str_replace(".", ",", round($thisWeekRecords['price'],2));
            $thisWeekRecords['euro'] = str_replace(".", ",", $thisWeekRecords['euro']);
            $thisWeekRecords['zmp'] = str_replace(".", ",", $thisWeekRecords['zmp']);
            $thisWeekRecords['week'] = $wn;

			$theme->assign('thisWeekZMP', $thisWeekRecords);
			$theme->assign('prevWeekZMP', $prevWeekRecords);
			$theme->assign('week_loads', $week_loads);
			$theme->assign('all_loaded_week',$all_loaded_week);
			$theme->assign('loadings_sum_of_day',$loadings_sum_of_day);
            $theme->assign("transports_sum_of_day",$transports_sum_of_day);
            $theme->assign('days_zam',$days_zam);
            $week_transported = $this->sum_records($week_transported,$amount);
            $theme->assign("sumary_week",$sum_week);
            $theme->assign("week_bought",$week_amount_sum);
            $theme->assign("week_transported",$week_trans);
            $theme->assign('days_text',$days_text);
            $theme->assign('missing',$missing);
            $theme->assign('missing_all',$missing_all);
            $theme->assign('all_bought',$all_bought_week);
            $theme->assign('all_transp',$all_transported_week);
            $theme->assign('amount_sum',$amount_sum);
            $theme->assign('start',1);
            $theme->assign('days',$days);
            $theme->assign('week_number', $wn);
            $theme->assign ( 'action_buttons', $buttons );
            $theme->display();
        }
        else if ($_REQUEST['mode'] == 'day' || $_REQUEST['mode'] == 'week' || $_REQUEST['mode'] == 'firma'){
            // dzienne zestawienie
            $day = $_REQUEST['date'];
            $day = strtotime($day);
            Base_ActionBarCommon::add(
                'back',
                "Wróć",
                $this->create_href ( array ()),
                null,
                0
            );
            $companes = new RBO_RecordsetAccessor("company");
            $transported = new RBO_RecordsetAccessor("custom_agrohandel_transporty");            //custom_agrohandel_transporty Transport
            $bought = new RBO_RecordsetAccessor("custom_agrohandel_purchase_plans");//zmien przed produkcja 
            $theme->assign("css", Base_ThemeCommon::get_template_dir());
            $transports = null; 
            $date = new PickDate($year);
            if($_REQUEST['mode'] == 'day'){
                Base_ActionBarCommon::add(
                    Base_ThemeCommon::get_template_file($this->get_type(), 'prev.png'),
                    "Poprzedni dzień",
                    $this->create_href ( array ('date' => date('Y-m-d',($day-60*60*24)),'mode'=>'day')),
                    null,
                    1
                );
                Base_ActionBarCommon::add(
                    Base_ThemeCommon::get_template_file($this->get_type(), 'next.png'),
                    "Następny dzień",
                    $this->create_href ( array ('date' => date('Y-m-d',($day+60*60*24)),'mode'=>'day')),
                    null,
                    2
                );
                $data = $_REQUEST['date'];
                $theme->assign('day',"Dzień: ".$data);
                $transports = $transported->get_records(array('date' => $data),array(),array());  
            }
            else if ($_REQUEST['mode'] == 'week'){
                Base_ActionBarCommon::add(
                    Base_ThemeCommon::get_template_file($this->get_type(), 'prev.png'),
                    "Poprzedni tydzień",
                    $this->create_href ( array ('date' => ($_REQUEST['date'] - 1),'mode'=>'week')),
                    null,
                    1
                );
                Base_ActionBarCommon::add(
                    Base_ThemeCommon::get_template_file($this->get_type(), 'next.png'),
                    "Następny tydzień",
                    $this->create_href ( array ('date' => ($_REQUEST['date'] + 1),'mode'=>'week')),
                    null,
                    2
                );
                $week = $_REQUEST['date'];
                $start_date = $date->monday_of_week($week); ;
                $end_date = $date->add_days($date->monday_of_week($week),6);
                $theme->assign('day',"Tydzień: ".$week. " (".$start_date." - ".$end_date." )");
                $transports = $transported->get_records(array('>=date' => $start_date, '<=date' => $end_date),array(),array());
            }
            else if ($_REQUEST['mode'] == 'firma'){
                $data = $_REQUEST['date'];
                $company = $_REQUEST['firma_id'];
                $company = $companes->get_record($company);
                $company_name = $company->get_val('company_name',$nolink=FALSE);
                $theme->assign('day',"Dzień: ".$data. " - ".$company_name);
                $transports = $transported->get_records(array('date' => $data,'company'=> $_REQUEST['firma_id']),array(),array());  
            }
			
			
			$suma_zal = 0;
            $suma_rozl = 0;
            $suma_bought = 0;
            $suma_dead = 0;
            $suma_przej = 0;
            $suma_plan = 0;

            //podliczenie 
			
            foreach($transports as $transport){
                $suma_rozl += $transport['iloscrozl'];
                $suma_dead += $transport['iloscpadle'];
                $suma_przej += $transport['kmprzej'];
                $suma_plan += $transport['kmplan'] ;
                $click = planerCommon::getVechicleInfo($transport);
                $transport['link'] = $click;
                $zakupy = $transport['zakupy'];
                foreach($zakupy as $zakup){
                    // suma z dnia poprzez zapupy przypiete pod tranport
                    $record = $bought->get_record($zakup);
                    $suma_bought += $record['amount'];
                    $suma_zal += $record['sztukzal'];
                    $transport['bought'] += $record['amount'];    
                    $transport['loaded'] += $record['sztukzal'];

                }
                $args = array();
                // wyswietlenie info w chmurze 
                foreach($zakupy as $zakup){
                    $record = $bought->get_record($zakup);
                    $company = $companes->get_record($record['company']);  //zmien przed produkcja
                    $company_name = $company->get_val('company_name',$nolink=True);
                    $args[$company_name] += $record['amount']."/".$record['sztukzal']."<br>";
                }                
                $infobox = Utils_TooltipCommon::format_info_tooltip($args);
                $transport['bought'] = Utils_TooltipCommon::create($transport['bought'],$infobox,$help=true, $max_width=300);
                if($transport['iloscrozl'] == "" or $transport['iloscrozl'] == null){
                    $transport['iloscrozl'] = 0;
                }
                if($transport['kmplan'] == "" or $transport['kmplan'] == null){
                    $transport['kmplan'] = 0;
                }
                if($transport['kmprzej'] == "" or $transport['kmprzej'] == null){
                    $transport['kmprzej'] = 0;
                }
                if($transport['iloscpadle'] == "" or $transport['iloscpadle'] == null){
                    $transport['iloscpadle'] = 0;
                }
                if($transport['loaded'] == "" or $transport['loaded'] == null){
                    $transport['loaded'] = 0;
                }
            }
            $sumy = array(1=>$suma_bought,2=>$suma_rozl,3=>$suma_dead,4=>$suma_plan,5=>$suma_przej,6=>$suma_zal);


            $theme->assign("sumy",$sumy);
            $transports = Rbo_Futures::set_related_fields($transports, 'company'); //zmien przed produkcja
            $theme->assign("transports",$transports);
            $theme->display('day');

        }
        else if($_REQUEST['mode'] == 'drivers'){
            $rbo_drivers = new RBO_RecordsetAccessor('contact');
            $rbo_transports = new RBO_RecordsetAccessor("custom_agrohandel_transporty");
            $drivers = $rbo_drivers->get_records(array('group' => array('u_driver')),array(),array());
            $date = new PickDate($year);
            $_date = $date->monday_of_week($_REQUEST['date']);
            $start = date('Y-m-01', strtotime($_date));
            $stop = date('Y-m-t', strtotime($_date));
            $last = date("t",strtotime($_date));
            $first = date("N",strtotime($start)); 
            $days= array();
            for($i=1;$i<$first;$i++){
                $days[] = array('num' => " ");
            }
            for($i=1;$i<=$last;$i++){
                $x = $i + $first;
                $days[$x] = array('num' =>  $i, 'ilosc' => 0);
            }
            $name_of_month = date('F', strtotime($_date));
            $name_of_month = __($name_of_month);
            $name_of_month .= " (".$start." - ". $stop.")";
            $raport = array();
            $driver_array = array();
            $raport_sumy = array(1=>0,2=>0,3=>0);
            foreach($drivers as $driver){
                $id = $driver->id;
                $transports = $rbo_transports->get_records(array('driver_1' => $id,'>=date' => $start ,
                '<=date' => $stop, '>iloscrozl' => 0  ),array(),array());
                if($transports != null || count($transports) > 0){
                    $name = $driver['last_name']." ".$driver['first_name'];
                    $raport[$id]['name'] = $name;
                    $identify = $driver['last_name'];
                }
                foreach($transports as $transport){
                    $index = date("j",strtotime($transport['date']));
                    $driver_array[$index][$identify]['ilosc'] += $transport['iloscrozl']; 
                    $driver_array[$index][$identify]['km'] += $transport['kmprzej']; 
                    $driver_array[$index][$identify]['name'] = $name; 
                    $index += $first;
                    $days[$index]['ilosc'] += $transport['iloscrozl']; 
                    $days[$index]['km'] += $transport['kmprzej']; 
                    $raport[$id]['szt'] += $transport['iloscrozl']; 
                    $raport_sumy[1] += $transport['iloscrozl'];
                    $raport[$id]['kmplan'] += $transport['kmplan']; 
                    $raport_sumy[2] += $transport['kmplan'];
                    $raport[$id]['kmprzej'] += $transport['kmprzej']; 
                    $raport_sumy[3] +=  $transport['kmprzej'];
                }

            }
            $theme->assign("raports",$raport);
            $theme->assign("raport_sumy",$raport_sumy);
            $theme->assign("days",$days);
            $theme->assign("drivers",$driver_array);
            $theme->assign("name_of_month",$name_of_month);
            $theme->display('raport');
            //Epesi::load_js("modules/planer/theme/drivers.js");
            Epesi::js(' function hidd(el){
                jq(el).parent().addClass("hidden");
                jq(el).parent().removeClass("visable");
            }');
            Epesi::js('
                jq(".slideDown").bind("click",function(){
                    var x = jq(this).parent().children(".day_drivers");
                    jq(x[0]).addClass("visable");
                    jq(x[0]).removeClass("hidden");
                    });
                    ');
        }

    }
    public function sum_records($records,$columnName){
        $value = 0;
        foreach($records as $record){
           $value += $record[$columnName];
        }
        return $value;
    }
}

class PickDate{
    private $year;

    function __construct($y) {
        $this->year = $y;
    }
    public function current_day(){
        $date = date("$this->year-m-d");
        return $date; 
    }

    function update_year($year){
        $this->year = $year;
    }
    public function this_week_start($date){
        $week = date("$this->year-m-d", strtotime('monday this week',strtotime($date)));
        return $week;
        
    }

    public function monday_of_week($number_of_week){
        $y = $this->year;
        if(strlen($number_of_week) ==  1){
            $number_of_week = "0".$number_of_week;
        }
        $week = date("$y-m-d", strtotime($this->year.'W'.$number_of_week));

        return $week;
    }
    public function add_days($start_date,$numbers_of_day_to_add){

        $date = strtotime($start_date);
        $days = $numbers_of_day_to_add*(60*60*24);
        $date = $date + $days;
        $date = date("Y-m-d",$date);
        return $date;
    }
    
    public function get_week_number($date){
        if(isset($date)){
        $week = date("W",strtotime($date));
        }
        else{
            $week = date("W");
        }
        if($week == "01"){
            $week = "53";
        }
        return $week;
    }
    public function get_day($date){
        return date("$this->year-m-d", strtotime($date));
    }
    
    public function get_week_name($date){
        return date('l', strtotime($date));
    }
    
}
class Rbo_Futures{
    public static function set_related_fields($varible, $name){
           foreach($varible as $edit){
            $edit[$name] = ($edit->get_val($name));
        }
        return $varible;
    }
}

class Addons{
    public static function can_copy($week_selected,$year){
        $y = $year;
        $w = $week_selected;
        // copied = 1 nocopied = 0
        if($week_selected- 1 < 1 ){
            $y -= 1;
            $w = 53;
        }else{$w -= 1;}

        $settings = fopen("settings.txt", "rw");
        $can = true;
        $date = new PickDate($year);
        $this_week = $week_selected."-".$y;
        $last_week = $w;
        $data = fread($settings,filesize('settings.txt'));
        fclose($settings);
        $data =  explode("\n", $data);
        foreach($data as $day){
            if($day == $this_week){
                $can = false;
            }
        }
        return $can;
    }
    public static function copied($week,$year){

        $date = new PickDate($year);
        $today = date("Y-m-d");
        $week = $week."-".$year;
        $settings = fopen("settings.txt", "a");
        fwrite($settings, "\n". $week);
        fclose($settings);
    }
}