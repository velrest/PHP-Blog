<script>
    tinyMCE.init({
            // General options
        mode : "exact",
        elements : "editor",
        setup : function(ed) {
            ed.on("keyup", checkPostData);
        }
    });
    $(".getBlog").click(showBlog);
</script>
<?php
    session_start();
    $clicked_sub_menu = $_POST["submenu"];
    $db = new SQLite3('..\SQL\blog.db');
    switch ($clicked_sub_menu) {
        case "create_post":
            print("
                <p>Titel:</p>
                <p id='title_alarm' class='alarm'>Bitte geben Sie einen Titel ein.</p>
                <input id='post_title' onkeyup='checkPostData()'><br><br>
                <p id='content_alarm' class='alarm'>Sie haben noch keinen Inhalt.</p>
                <textaera id='editor'/>
                <p>Kategorie:
                <select id='post_category'>
                   <option value='Computer'>Computer</option>
                   <option value='Natur'>Natur</option>
                   <option value='Fotografie'>Fotografie</option>
                   <option value='Malerei'>Malerei</option>
                   <option value='Games'>Games</option>
                   <option value='Musik'>Musik</option>
                   <option value='Reisen'>Reisen</option>
                </select></p><br>
                <button id='add_post_button' onclick='add_post()' style='display: none'>Erstellen</button>
        ");
        break;

        case "add_post":
            $content = str_replace("%**%equals", "=", $_POST["post_content"]);
            $content = str_replace("%**%", "&", $content);
            $result = $db->querySingle("select exists(select Title from articles
                where Title = '".$_POST["post_title"]."')");
            if ($result) {
                print("
                    <div id='error' class='content_div'>
                        <p>
                            <h1>Dieser Titel ist schon vergeben.</h1><br>
                            Bitte Versuchen Sie es mit einem anderen Titel.
                        </p>
                    </div>
                ");
            } else {
                $db->exec("insert into articles (Title, Content) values ('".$_POST["post_title"]."','".$content."')");
                $resultaid = $db->querySingle("select ArticleID from articles
                    where Title = '".$_POST["post_title"]."'");
                $resulttid = $db->querySingle("select ThemeID from themes
                    where ThemeName = '".$_POST["post_category"]."'");
                $db->exec("insert into articles_themes (IDArticle, IDTheme) values ('".$resultaid."', '".$resulttid."')");
                $db->exec("insert into users_articles values('".$resultaid."', '".$_SESSION["USERID"]."')");

                print("
                <div class='content_div'>
                    <h1>Ihr Artikel wurde erstellt</h1>
                    Sie können Ihn nun unter der von Ihnen angegebenen Kategorie oder in Ihrem Benutzerprofil abrufen
                ");
            }
            break;

        case "uhome":
            print("<div class='content_div'><h1>".$_SESSION["USERNAME"]."</h1></div>");

            $result = $db->querySingle("select count(ArticleID) from users_articles
              inner join articles on articles.ArticleID=users_articles.IDArticle
              inner join users on users.UserID=users_articles.IDUser
              where UserId = '".$_SESSION["USERID"]."'");

            print("
              Posts: ".$result."<br>
              <button id='create_post_button' onclick='create_post()'>Post erstellen</button><br><br>
              <h3>Meine Posts:</h3></div><br>
            ");

            $result = $db->query("select ArticleID, Title, Content from articles_themes
              inner join articles on articles.ArticleID=articles_themes.IDArticle
              inner join themes on themes.ThemeID=articles_themes.IDTheme
              inner join users_articles on users_articles.IDArticle=articles.ArticleID
              inner join users on users.UserID=users_articles.IDUser
              where UserId = '".$_SESSION["USERID"]."'
              order by ArticleID desc");

            $row = array();

            $i = 0;

            while($res = $result->fetchArray(SQLITE3_ASSOC)){

               $row[$i]['id'] = $res['ArticleID'];
               $row[$i]['title'] = $res['Title'];
               $row[$i]['content'] = $res['Content'];

                $i++;

            }
            for ($i=0; $i < count($row); $i++) {
                print("<div class='content_div'><h2 id='".$row[$i]['id']."' onclick='load_post(".$row[$i]["id"].")'>".$row[$i]['title']."</h2>");
                $out = strlen($row[$i]['content']) > 500 ? substr($row[$i]['content'],0,500)."..." : $row[$i]['content'];
                print($out."</div>");
            }
            break;

        case "show_blog":
            $anzPosts = $db->querySingle("select count(ArticleID) from articles
            inner join users_articles on users_articles.IDArticle=articles.ArticleID
            inner join users on users.UserID=users_articles.IDUser
            where Username = '".$_POST["user"]."'");
            print("<div class='content_div'>
                    <h1>".$_POST["user"]."</h1>
                </div>
                <p>Anzahl Posts: ".$anzPosts."</p>");
            $row = listPosts("Username", $_POST["user"], "ArticleID", "desc", $db);
            for ($i=0; $i < count($row); $i++) {
                print("<div class='content_div'><h2 id='".$row[$i]['id']."' onclick='load_post(".$row[$i]["id"].")'>".$row[$i]['title']."</h2>");
                $out = strlen($row[$i]['content']) > 500 ? substr($row[$i]['content'],0,500)."..." : $row[$i]['content'];
                print($out."</div>");
            }
            break;

        case "post":
            $clicked_post = $_POST["id"];
            $result = $db->querySingle("select Title, Username, Content from articles_themes
                inner join articles on articles.ArticleID=articles_themes.IDArticle
                inner join themes on themes.ThemeID=articles_themes.IDTheme
                inner join users_articles on users_articles.IDArticle=articles.ArticleID
                inner join users on users.UserID=users_articles.IDUser
                where ArticleID = ".$clicked_post, true);

            print("
                <div class='content_div' id='post'>
                    <h2 id='show_content'>".$result["Title"]."</h2>
                    <p name='".$result["Username"]."' class='getBlog'>".$result["Username"]."</p>
                    ".$result["Content"]."
                </div>
            ");

        default:
            $row = listPosts("ThemeName", $clicked_sub_menu, "ArticleID", "desc", $db);

            for ($i=0; $i < count($row); $i++) {
                print("<div class='content_div'><h2 id='".$row[$i]['id']."' onclick='load_post(".$row[$i]["id"].")'>".$row[$i]['title']."</h2>");
                print("<p name='".$row[$i]['username']."' class='getBlog'>Blog: ".$row[$i]['username']."<br></p><br>");
                $out = strlen($row[$i]['content']) > 500 ? substr($row[$i]['content'],0,500)."..." : $row[$i]['content'];
                print($out."</div>");
            }
    }

    function listPosts($searchedRow, $searchedColumn, $orderByRow, $orderType, $db) {
      $result = $db->query("select ArticleID, Title, Username, Content from articles_themes
          inner join articles on articles.ArticleID=articles_themes.IDArticle
          inner join themes on themes.ThemeID=articles_themes.IDTheme
          inner join users_articles on users_articles.IDArticle=articles.ArticleID
          inner join users on users.UserID=users_articles.IDUser
          where lower(".$searchedRow.") = lower('".$searchedColumn."')
          order by ".$orderByRow." ".$orderType."");

      $row = array();

      $i = 0;

       while($res = $result->fetchArray(SQLITE3_ASSOC)){

           $row[$i]['id'] = $res['ArticleID'];
           $row[$i]['title'] = $res['Title'];
           $row[$i]['username'] = $res['Username'];
           $row[$i]['content'] = $res['Content'];

            $i++;

        }
        return $row;
    }
?>
