<?php
// Documentation located in README. Put your info in first 3 lines.
class Blog {
	private $password = "breck";
	public $blog_title = "Breck Yunits' Blog";
	public $blog_description = "My weblog where I write my thoughts.";
	public function __construct()
	{
		include("posts.php");
		$this->posts = $posts;
		foreach ($posts as $key => $array)
		{
			$this->titles[strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$array['Title'])))] = $key;
		}
	}
	public function saveBlog()
	{
	
	}
	public function displayEditor ($post_timestamp = 0)
	{
	
	}
	public function controller()
	{
		if (isset($_GET['r']) && $_GET['r'] == "/editor")
		{
			$this->displayEditor();
		}
		elseif (isset($_GET['r']) && isset($this->titles[substr($_GET['r'],1)]) )
		{
			$post = $this->posts[$this->titles[substr($_GET['r'],1)]];
			$this->displayPage($post['Title'],substr($post['Essay'],0,100),
			"<h1>{$post['Title']}</h1><div>{$post['Essay']}</div>");
		}
		else { // Homepage
			$last_five = "";
			foreach ($this->posts as $post)
			{
				$last_five .= "<h1><a href=\"".strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$post['Title'])))."\">{$post['Title']}</a></h1><div>{$post['Essay']}</div>";
			}
			$this->displayPage($this->blog_title, $this->blog_description,
			$last_five); 
		}
	}
	public function displayPage($title, $description, $body)
	{
		?>
			<html>
			<head>
			<style>
			body {
				font-family: Georgia;
				color: #888888;
			}
			#sidebar {
				float: right;
			}
			</style>
			<title><?php echo $title;?></title>
			<meta name="description" content="<?php echo $description;?>">
			</head>
			<body>
			<?php
			echo $body;
			?>
			<div id="sidebar">
				<a href="/">Home</a><br>
				<?php foreach ($this->posts as $post)
				{
					?><a href="/<?php 
					echo strtolower(str_replace(" ","_",preg_replace('/[^a-z0-9 ]/i',"",$post['Title'])));
					?>"><?php echo $post['Title'];?></a><br><?php
				}
				?>
			</div>
			</html>
		<?php
	}
}
$blog = new Blog;
$blog->controller();
?>
