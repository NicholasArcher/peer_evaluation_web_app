<!DOCTYPE html>
<html>
<head>
    <title>Team Peer Evaluation - Processor</title>

    <style>
        div {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <?php

    $dbuser = 'root';
    $dbpass = 'test1234';
    $dbname = 'forms1';
    
    $conn = new mysqli("localhost", $dbuser, $dbpass, $dbname, 4000);
    if ($conn->connect_error) {
      die('Connection Failed: ' . $conn->connect_error);
    }

    $projectname = strip_tags($_POST['projectname']);
    $starttime = strtotime($_POST['starttime']);
    $stoptime = strtotime($_POST['stoptime']);
    $membersRaw = $_POST['members'];
    $members = [];
    foreach ($membersRaw as $key => $memberRaw) {
        $membername = strip_tags($memberRaw['name']);
        $memberparticipation = intval($memberRaw['participation']);
        $members[] = ["name"=>$membername, "participation"=>$memberparticipation];
    }

    $notes = strip_tags($_POST['notes']);
    $teamgrade = strip_tags($_POST['teamgrade']);


    $presentationtime = ($stoptime - $starttime)/60;

    $possiblegrades = ["A+", "A", "A-", "B+", "B", "B-", "C+", "C", "C-", "D+", "D", "F"];
    $teamgradeindex = array_search($teamgrade, $possiblegrades);

    function determinegrade($member) {
        global $possiblegrades, $teamgradeindex;
        $participation = $member['participation'];
        $grade = $teamgradeindex - ($participation - 3);
        $grade = min($grade, count($possiblegrades) - 1);
        $grade = max($grade, 0);
        return $member + ["grade" => $possiblegrades[$grade]];
    }
    $members = array_map('determinegrade', $members);

    $sql = <<<SQL
        INSERT INTO groups
            (name, duration, grade, notes)
            VALUES ('$projectname', '$presentationtime', '$teamgrade', '$notes');
        SET @group_id = LAST_INSERT_ID();
SQL;

    foreach ($members as $key => $member) {
        $name = $member['name'];
        $grade = $member['grade'];
        
        $sql .= <<<SQL
                INSERT INTO group_members
                    (name, grade, group_id)
                    VALUES ('$name', '$grade', @group_id);
SQL;
    }

    /* Execute query and make sure that it succeeded */
    if (!$conn->multi_query($sql)) {
        echo "Error: $conn->error<br /><pre>$sql</pre><br />";
    }
    /* close the database connection */
    $conn->close();


    /* render the page */
    $memberList = "";
    foreach ($members as $key => $member) {
        $memberList .= <<<HTML
            <span>Member name: {$member['name']}</span>
            <span>Member grade: {$member['grade']}</span><br />
HTML;
    }

    echo <<<HTML
        <span> 
            Project name: $projectname
        </span>
        <br />

        <span> 
            Presentation time: $presentationtime minutes
        </span>
        <br />

        <span> 
            Overall team grade: $teamgrade
        </span>
        <br />
        <br />

        {$memberList}

        <span> 
            Notes: $notes
        </span>
HTML;
    ?>	

</body>
</html>
