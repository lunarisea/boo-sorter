<?php 
include("mytcg/settings.php"); 
global $db_server;
global $db_user;
global $db_password;
global $db_database;
$mysqli = new mysqli($db_server, $db_user, $db_password, $db_database);

?>

<h1>Boo Fairy List</h1>

<?php

    /*
        These are the mods that will be handling trades. feel free to add as many as you like or remove any as changes occur. The script should be flxible enough that it responds to changes in the list. Just be sure that players' names match their name on the members list!
        */
    $mods = "mod1, mod2, mod3";
    //filename where data will be saved
    $file = 'boofairies.txt';

    //turn mods into an array and randomize the order for later
    $_mods = explode(', ', $mods);
    shuffle($_mods);
    

    function get_players() {
        global $table_members;
        global $mysqli;

        $players = [];

        $result = $mysqli->query("SELECT * FROM `$table_members` WHERE `status`='Active'");

        if( $result ) {

            $count = $result->num_rows;

            while($row = $result->fetch_array()) {
                array_push($players, $row['name']);
            }

            $result->close();
            
        } else {
            echo "Couldn't connect to Database";
            $result->error();
        }

        return $players;
    }

    function add_leftover_players($players) {

        foreach($_mods as $mod) {
            
            $split_list[$mod] = [];

            foreach($members_left as $player) {

                $mod_matches_player = strcmp($player, $mod);

                if($mod_matches_player !== 0) {

                    array_splice($members_left, $offset, 1);

                    $player_to_add = $player;
                    
                    array_push($members_assigned, $player_to_add);
                    array_push($split_list[$mod], $player_to_add);
                    $num_players_assigned++;
                    $offset = 0;
                } else {
                    $offset += 1;
                }

                if($num_players_assigned == $players_per_list) {
                    $num_players_assigned = 0;
                    break 1;
                }
                
            }
        }
    }

    function split_players($players) {
        global $_mods;
        shuffle($players);

        //set up the variables for the logic
        $num_mods = count($_mods);
        $num_players = count($players) - $num_mods;
        $players_per_list = ceil($num_players/$num_mods);

        $_mod_array = [];
        
        foreach($_mods as $mod) {
            $index = array_search($mod, $players);
            array_splice($players, $index, 1);
            array_push($_mod_array, $mod);
        }

        $split_array = array_chunk( $players, $players_per_list);

        return $split_array;
    }

    function add_mods_to_player_list($players) {
        global $_mods;

        $mods_left = $_mods;
        $mod_removed = array_shift($mods_left);
        array_push($mods_left, $mod_removed);

        $i=0;
        foreach($_mods as $mod) {
            array_push($players[$i], $mod);
            $i++;
        }

        return $players;
    }

    function reorder_mod_list($mods) {
        global $_mods;

        $mod_removed = array_shift($_mods);
        array_push($_mods, $mod_removed);

        return $_mods;
    }

    function write_to_file($player_list, $mod_list) {
        global $file;

        date_default_timezone_set('Canada/Atlantic');
        $date = date('F dS, Y h:ia (l)', time());

        $contents = '';

        $contents .= 'Last generated: '.$date;
        $contents .= "\n";

        $i = 0;
        foreach($player_list as $player_group) {
            $contents .= "\n";
            $contents .= '----------- '.$mod_list[$i].' -----------';
            $contents .= "\n";
            foreach($player_group as $player) {
                $contents .= '- '.$player."\n";
            }
            $i++;
        }

        $file_new = file_put_contents($file, $contents);
    }

    function read_from_file($player_list, $mod_list) {
        global $file;

        $file_contents = file_get_contents($file);

        echo nl2br($file_contents);

        
    }

    function print_list($player_list, $mod_list) {

        $i = 0;
        foreach($player_list as $player_group) {

            echo '<h2>'.$mod_list[$i].'</h2>';
            echo '<ol>';
            foreach($player_group as $player) {
                echo '<li>'.$player.'</li>';
            }
            echo '</ol>';
            $i++;
        }
    }
    
    $players = get_players();
    $players = split_players($players);
    $players = add_mods_to_player_list($players);
    $mods = reorder_mod_list($mods);
?>


<form method="post" name="splitform" id="splitform">
    <button name="splitbutton" id="splitbutton">Split Player List</button>
</form>

<?php
if (isset($_POST['splitbutton'])) {
    write_to_file($players,$mods);
    read_from_file();
} else {
    read_from_file();
}
?>