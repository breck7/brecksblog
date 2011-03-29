## brecksblog README

### What is brecksblog?

brecksblog is simple blogging software written in PHP that you can run on your own domain.

### How do I install it?

1. Put index.php in the directory where you want your blog to be.
2. Make the directory writeable.
3. Run index.php. Done!

### What makes brecksblog different from other blogging software?

brecksblog is fast and simple. brecksblog is 1 file and does not use a database. It is less than 400 lines of code. This one file includes not only the app itself, but a one click installer and updater. 

### Who is it for?

For people that want to get started blogging fast, or experiment with a lot of blogs.
You can create a new blog in under 60 seconds. You can create 10 new blogs in under 10 minutes.

### What are some alternatives to brecksblog?

Wordpress, Tumblr, Posterous, Blogger, Blogspot, Typepad and Movable Type, to name a few.

### Do I have to know how to code to use brecksblog?
No. However, if you know HTML, CSS, and/or JavaScript, you can get even more out of brecksblog

### What are the requirements? 
You need PHP and Apache with mod_rewrite for it to work.

### Benefits

- Easy. Even your mom can use it (mine does)!
- Productive. Simple software forces you to focus on what's important in blogging: content.
- Extendible. Change the theme. Add markdown. Add javascript. All of these steps take a few seconds.
- Reliable. Has held up without a hiccup on some high traffic sites. Open source and small file size means anyone can fix or extend it.
- Painless. No complicated database. Only one file.

### Features
- No login needed. Just use your password each time you edit a page. Counterintuitive, but pretty fun.
- Create multiple blogs fast. Just make one folder per blog.
- Drop markdown.php into your blog folder to easily add markdown support.
- Stores all data in one text file (data.php). No database needed.
- Easy to write, edit, and delete posts. Easy to chang CSS, footer, header, etc.
- Less than 400 lines of code. Easy to extend and modify.
- Simple, clean layout. Easy to change CSS.
- Easily change the layout or add analytics, etc.
- Works great under loads (so far).
- Easy 1 file install.
- Easy 1 click updates.
- No clutter! No comments, etc., (but you can add them easily using disqus or intense debate)
- Easy to add real time stats.

### Known Issues

- File permissions. Your file permissions have to be set correctly. Make sure the directory is writeable during install and index.php is writeable if you want to use the updater.
- Mod_rewrite. You need mod_rewrite enabled for it to work.
- Untested. This software has not been thoroughly tested.
- Password. If someone guesses your password, they can change your posts and settings.
- Brute force password guessing. There is no protection yet against brute force guessing attempts.
- Things that may break it: posts with the same timestamp or name. Having lots of posts. Getting lots of traffic while editing.
- No backups. There is no system yet to auto backup your posts and settings. This will be added.

### How do I get help?

Contact the admins through github or breck7 at gmail.com.

### Who contributes to brecksblog?

Breck Yunits(breck7), Doug Tyler(dougwt), Tyler(chacha)

### License

Released under the MIT license.

### Todo

- Get it under 300 lines.
- Reduce long lines to under 80 characters.
- Better security (specifically against brute force attacks)
- Generate sitemaps for Google?
- Auto submit to blog search engines?
- Make it easy to include external Javascript libraries for:
> backup/autosave(etherpad like)
> wysiwyg
> comments
- haml support?
- plugin architecture
- unit tests
- integrate real time stats
- integrate post upvoting/downvoting
- integrate similar posts