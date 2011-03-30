<?php
/**
 * brecksblog is simple blogging software written in 1 page of PHP that
 * you can run on your own domain with no database.
 *
 * How to install:
 * 1. Put index.php in the directory where you want your blog to be.
 * 2. Make the directory writeable.
 * 3. Run index.php. Done!
 *
 * @author    Breck Yunits
 * @license   MIT
 * @url       http://github.com/breck7/brecksblog
 */
class BrecksBlog {

  /**
   * Version number.
   */
  public $version = "0.9.3";

  /**
   * Default settings for a fresh install.
   */
  public $settings = array(
    "BLOG_TITLE" => "My blog",
    "BLOG_DESCRIPTION" => "A blog experiment.",
    "BLOG_HEADER" => "",
    "BLOG_HEAD_SCRIPTS" => "",
    "BLOG_NAVIGATION_HEADER" => "<a href=\"index.php\">Home</a><br>",
    "BLOG_NAVIGATION_FOOTER" => '<br><a href="feed">RSS</a><a href="write" rel="nofollow">Admin</a>',
    "BLOG_FOOTER" => "Powered by <a href=\"http://brecksblog.com\">brecksblog</a>",
    "POST_FOOTER" => "",
    "BLOG_CSS" => "body {font-family: arial; color: #222222; padding: 10px;}
h1 {margin-top: 0px; border-bottom: 1px solid #999999; font-size:26px;}
h1 a {text-decoration:none; color: #0000AA;}
h2 {margin-top: 0px;}
#content {float:left; width:70%; margin-right:10px;}
#left_column {}
#right_column {font-size:.8em; background: #F9F9F9; float: left; width: 25%; padding: 8px;}
#right_column a{display: block; padding: 3px; text-decoration:none; color:#0000AA;}
#right_column a:hover {background: #f9f9aa;}
#footer {clear: both; text-align: center; font-size: .8em; color: #888888; padding-top: 20px;}
.dateposted {margin: 15px 0px;}",
  );
  
  /**
   * Default htaccess file for a fresh install.
   */
  private $htaccess = "RewriteEngine on
RewriteCond %{HTTP_HOST} ^www\.(.*) [NC]
RewriteRule ^(.*) http://%1/$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ index.php?r=%{REQUEST_URI}&%{QUERY_STRING}
IndexIgnore *";

  /**
   * Checks to make sure blog is installed, loads settings, posts, and then
   * runs the controller.
   */
  public function __construct()
  {
    // Set default url dynamically.
    $this->settings['BLOG_URL'] = "http://".$_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $this->installIfNotInstalled();    // Run install routine if a new blog.
    include("data.php"); // Load the posts and settings file.
    $this->posts = $data['posts'];
    $this->password = $data['password'];
    $this->blocked_ips = (isset($data['blocked_ips']) ? $data['blocked_ips'] : array());
    // Overwrite the default settings with the user's settings 
    foreach ($data['settings'] as $key => $value) {
      $this->settings[$key] = $value;
    }
    foreach ($this->settings as $key => $value) {
      define($key, $value);
    }
    // Build the pretty urls array.
    foreach ($this->posts as $key => $array) {
      $this->titles[$this->createPrettyPermalink($array['Title'])] = $key;
    }
    // Now, the main controller
    if (isset($_GET['r'])) 
    {
      $url = array_pop(explode("/", $_GET['r']));  // Get the Redirect Path
      if ($url == "write")
      {
        $this->modifyPost();
        $this->echoAdminPage();
      }
      elseif ($url == "upgrade" && $this->isPasswordCorrect())
      {
        file_put_contents("index.php", file_get_contents("http://brecksblog.com/newest/index.php")) or die("File permission problem. Change the file permissions on this directory.");
        $this->flashSuccessMessage("Blog updated! <a href=\"write\">Admin</a>"); exit;
      }
      elseif ($url == "upload" && $this->isPasswordCorrect())
      {
        if (!preg_match('/(gif|jpeg|jpg|png|mov|avi|xls|doc|pdf|txt|html|htm|css|js)/i',end(explode('.', $_FILES["file"]["name"]))))
        {
          die("You can't upload that type of file.");
        }
        move_uploaded_file($_FILES["file"]["tmp_name"], $_FILES["file"]["name"]);
        $this->flashSuccessMessage("File <a target=\"_blank\" href=\"{$_FILES["file"]["name"]}\">saved</a> as {$_FILES["file"]["name"]}");
        $this->echoAdminPage();
      }
      elseif ($url == "editsettings" && $this->isPasswordCorrect())
      {
        unset($_POST['password']); // Don't resave the password.
        $this->settings = $_POST;
        $this->saveData();
        $this->flashSuccessMessage("Settings saved.");
        $this->echoAdminPage();
      }
      elseif ($url == "json")
      {
        echo (isset($_GET['callback']) ? $_GET['callback'] : "") . json_encode($this->posts);
      }
      elseif ($url == "feed")
      { 
        $this->echoFeed();
      }
      elseif ( isset($this->titles[$url]) ) // Display a post
      {
        $post = $this->posts[$this->titles[$url]];
        $this->echoPage($post['Title'], substr($post['Essay'], 0, 100),
          "<h1 id=\"postTitle\" post_id=\"{$this->titles[$url]}\">{$post['Title']}</h1>" .
          "<div>" . $this->formatPost($post['Essay']) . "<br><br>" .
          "<div class=\"dateposted\">Posted " . date("m/d/Y",$this->titles[$url]) . "</div>" .
          POST_FOOTER . "</div>");
      }
      else
      {
        ?>Oops! File not found. <a href="index.php">Back to blog</a>.<?php
      }
    }
    else // Display Homepage
    {
      $all_posts = ""; // Might want to limit it to just a few recent posts.
      foreach ($this->posts as $key => $post)
      {
        $all_posts .= "<h2><a href=\"" . $this->createPrettyPermalink($post['Title']) . "\">{$post['Title']}</a></h2>";
        $all_posts .= "<div class=\"post_snippet\">" . $this->formatPost(substr(strip_tags($post['Essay']), 0, 150));
        $all_posts .= "<a href=\"" . $this->createPrettyPermalink($post['Title']) . "\">...continue to full essay.</a>";
        $all_posts .= "<div class=\"dateposted\">Posted " . date("m/d/Y", $key) . "</div></div>";
      }
      $this->echoPage(BLOG_TITLE, BLOG_DESCRIPTION, $all_posts); 
    }
  }


  /**
   * Prints the homepage as well as an individual post page.
   */
  public function echoPage($title, $description, $body)
  {
  ?><!doctype html>
<html>
  <head>
    <?php echo BLOG_HEAD_SCRIPTS ."\n"; ?>
    <style type="text/css">
      <?php echo BLOG_CSS; ?>
    </style>
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo str_replace('"',"",$description);?>" />
  </head>
  <body>
    <div id="header">
      <?php echo BLOG_HEADER; ?>
    </div>
    <div id="left_column">
      &nbsp;
    </div>
    <div id="content">
      <?php echo $body; ?>
    </div>
    <div id="right_column">
      <?php echo BLOG_NAVIGATION_HEADER; ?>
      <?php foreach ($this->posts as $post) { 
        echo '          <a href="'.$this->createPrettyPermalink($post['Title']).'">' . $post['Title'] . "</a>\n";
      }?>
      <?php echo BLOG_NAVIGATION_FOOTER; ?>
    </div>
    <div id="footer">
      <?php echo BLOG_FOOTER; ?>
    </div>
  </body>
</html><?php 
  }
  
  /**
   * Prints the RSS feed.
   */
  public function echoFeed()
  {
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="ISO-8859-1" ?>';
    ?>
<rss version="0.91">
  <channel>
    <title><?php echo BLOG_TITLE;?></title>
    <link><?php echo BLOG_URL;?></link>
    <description><?php echo BLOG_DESCRIPTION;?></description>
    <language>en-us</language>
    <?php 
    foreach ($this->posts as $post)
    {
      ?>
      <item>
      <title><?php echo $post['Title'];?></title>
      <link><?php echo BLOG_URL . $this->createPrettyPermalink($post['Title']);?></link>
      <description><?php echo $this->formatPost(str_replace("&", "&amp;", strip_tags($post['Essay'])));?></description>
      </item>
<?php
    } ?>
  </channel>
</rss><?php 
  }

  /**
   * Prints a success notice.
   */
  public function flashSuccessMessage($message)
  {
    echo "<span style=\"color:green;\">$message</span>";
  }

  /**
   * Returns true if correct password. Else, it dies.
   */
  public function isPasswordCorrect()
  {
    if (isset($_POST['password'])) {
      // blocked
      if (isset($this->blocked_ips[$_SERVER['REMOTE_ADDR']]) && $this->blocked_ips[$_SERVER['REMOTE_ADDR']]['count'] > 20 && $this->blocked_ips[$_SERVER['REMOTE_ADDR']]['last_attempt'] > (time()-1800)) {
        die("Too many bad password attempts. Wait 30 minutes then try again.");
      } // not blocked and correct
      elseif (md5($_POST['password'] . "breckrand") == $this->password) {
        if (isset($this->blocked_ips[$_SERVER['REMOTE_ADDR']])) {
          unset($this->blocked_ips[$_SERVER['REMOTE_ADDR']]);
          $this->saveData();
        }
        return true;
      } // not blocked and incorrect
      else {
        if (isset($this->blocked_ips[$_SERVER['REMOTE_ADDR']])) {
          $this->blocked_ips[$_SERVER['REMOTE_ADDR']]['count']++;
          $this->blocked_ips[$_SERVER['REMOTE_ADDR']]['last_attempt'] = time();
        } else {
          $this->blocked_ips[$_SERVER['REMOTE_ADDR']] = array('count' => 1, 'last_attempt' => time());
        }
        $this->saveData();
      }
    }
    die("Invalid Password");
  }

  /**
   * Takes a title and returns a clean string that makes a good permalink.
   */
  public function createPrettyPermalink($title_string) // cleans a string
  {
    return strtolower(str_replace(" ", "_", preg_replace('/[^a-z0-9 ]/i', "", $title_string)));
  }

  /**
   * Function that creates, updates, and deletes posts.
   */
  public function modifyPost()
  {
    if (count($_POST) && $this->isPasswordCorrect())
    {
      if (!isset($_GET['post'])) // create new post
      {
        $time = time();
        if (strlen($_POST['title']) < 1){ die("Title can't be blank"); }
        $this->posts[$time] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
        $this->flashSuccessMessage("<a href=\"".$this->createPrettyPermalink($_POST['title'])."\">Post created!</a> | <a href=\"write?post={$time}\">Edit it</a>");
      }
      elseif (isset($this->posts[$_GET['post']]) && isset($_POST['delete'])) // delete a post
      {
        unset($this->posts[$_GET['post']]);$this->saveData();
        $this->flashSuccessMessage("Post deleted. <a href=\"write\">Back</a>");exit;
      }
      elseif (isset($this->posts[$_GET['post']])) // edit a post
      {
        $this->posts[$_GET['post']] = array("Title" => $_POST['title'], "Essay" => $_POST['essay']);
        $this->flashSuccessMessage("<a href=\"".$this->createPrettyPermalink($_POST['title'])."\">Post updated!</a>");
      }
      krsort($this->posts); // Sort the posts in reverse chronological order
      $this->saveData();
    }
  }

  /**
   * Saves all settings and blog posts to disk in the data.php file.
   */
  public function saveData()
  {
    $data = array("posts" => $this->posts, "settings" => $this->settings, "password" => $this->password, "blocked_ips" => $this->blocked_ips);
    file_put_contents("data.php", "<?php \$data= ".var_export($data, true) . "?>");
  }

  /**
   * Prepares a post for displaying.
   */
  public function formatPost($post)
  {
    if (file_exists("markdown.php") && !preg_match('/^<nomarkdown>/',$post))
    {
      include_once("markdown.php"); 
      return Markdown($post);
    }
    return nl2br($post);
  }

  /**
   * Displays the blog admin page.
   */
  public function echoAdminPage()
  {
    $title_value = $essay_value = $delete_button = $edit_action = "";
    if (isset($_GET['post']) && isset($this->posts[$_GET['post']]))
    {
      $title_value = $this->posts[$_GET['post']]['Title'];
      $essay_value = $this->posts[$_GET['post']]['Essay'];
      $edit_action = "?post=" . (int)$_GET['post'];
      $delete_button = "<input type=\"submit\" value=\"Delete\" name=\"delete\" onclick=\"return confirm('DELETE. Are you sure?');\"><br><br><a href=\"write\">Create new post</a>";
    }
    is_writable("data.php") or die("WARNING! data.php not writeable");
    ?><!doctype html>
    <html>
    <head>
      <style>
        body {font-family: Arial;}
        div {margin-top: 10px;}
        table {width: 100%;}
        td {vertical-align: top;}
      </style>
    </head>
    <body>
      <?php if ($_SERVER['SERVER_PORT'] == 80) {echo "WARNING! Non-https connection. <a href=\"https".str_replace("index.php", "write", substr(BLOG_URL,4))."\">Switch to https</a>.";}?>
      <table cellpadding="10px"><tr>
        <td width="62.5%">
          <form method="post" action="write<?php echo $edit_action;?>">
            <table>
              <tr>
                <td>Title</td>
                <td style="width:100%;"><input type="text" name="title" style="width:100%;" value="<?php echo htmlentities($title_value)?>"></td>
              </tr>
              <tr>
                <td>Content</td>
                <td><textarea name="essay" rows="15" style="width:100%;"><?php echo $essay_value?></textarea></td>
              </tr>
              <tr>
                <td>Password</td>
                <td><input type="password" name="password"></td>
              </tr>
              <tr>
                <td></td>
                <td><input type="submit" value="Save"><?php echo $delete_button?></td>
              </tr>
            </table>
          </form>
        </td>
        <td style="color:#999999; background: #f9f9f9;">
          <div>
            <a href="index.php" style="text-decoration:none;"><?php echo BLOG_TITLE?></a>
          </div>
          <div>
            <b>Posts</b><br>
            <?php foreach ($this->posts as $key => $array) // display links to edit posts
            {
              echo "<a href=\"" . $this->createPrettyPermalink($array['Title']) . "\">{$array['Title']}</a> | <a href=\"write?post=".$key."\">edit</a><br>";
            }
            ?>
          </div>
          <div>
            <b>Upload File</b>
            <form action="upload" method="post" enctype="multipart/form-data">
              <input type="file" name="file">
              <br>Password<br><input type="password" name="password">
              <input type="submit" value="Upload">
            </form>
          </div>
          <div>
            <b>Settings</b>
            <form method="post" action="editsettings">
              <?php foreach ($this->settings as $key => $value)
              {?><?php echo ucfirst(strtolower(str_replace("_"," ",$key)));?><br><textarea style="width:100%;" rows="7" name="<?php echo $key;?>"><?php echo htmlentities($value);?></textarea><br><br><?php }?>
              Password<br><input type="password" name="password">
              <input type="submit" value="Save">
            </form>
          </div>
          <div>
            <b>Upgrade</b>
            <br>brecksblog version: <?php echo $this->version;?><br>
            <form action="upgrade" method="post">
              Password<br><input type="password" name="password"><input type="submit" value="Upgrade">
            </form>
          </div>
        </td></tr>
      </table>
    </body>
    </html><?php
  }

  /**
   * Checks to see if brecksblog is installed. If not, prompts for password
   * and runs install. If it is installed, returns.
   */
  public function installIfNotInstalled()
  {
    if (file_exists("data.php") || file_exists(".htaccess"))
        return; // Already installed.
    elseif (!isset($_POST['password']) || strlen($_POST['password']) < 1 ) 
    {
      file_put_contents("test_file_permissions","1") or die("WARNING! Directory not writeable. Change the file permissions before installing.");
      unlink("test_file_permissions");
      ?>
      <h2>Install brecksblog</h2>
      <form method="post">Choose a <b>strong</b> password <input name="password" type="password"><input type="submit" value="Install!"></form>
      <?php exit;
    }
    else // Run the install
    {
      file_put_contents(".htaccess", $this->htaccess);
      $this->password = md5($_POST['password'] . "breckrand");
      $this->posts = array( 1259736228 => array('Title' => 'Hello World',
        'Essay' => 'Your first blog post!'));
      $this->saveData();
    }
  }
}
$blog = new BrecksBlog;