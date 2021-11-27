#Anova PHP Library
This is a PHP Library to connect and control your
<a href="https://anovaculinary.com">Anova Sous Vide</a>. It includes a basic CLI demo app
which can schedule your anova to start at a given time.

##Background and usage
This library is based on the api info in https://github.com/ammarzuberi/pyanova-api.

To use the library you need to grab it and install the dependencies using composer. 
You can run it on pretty much any machine with PHP 8+. 

If you want to use docker, included is a docker image.
Simply run `./build.sh` and then `./run.sh`. From there
connect to your container and you can run the demo app.

You'll need your Anova credentials (email address and password)
and device ID. You can get your device ID by going into the 
Anova app, clicking profile->setting wheel up top. You
should see your device ID there. 

Be sure your Anova is wifi connected.

To use the demo app, you run demo.php. Here's the usage:

![help page for the demo](https://github.com/rizwanjiwan/anovaphp/raw/main/demo-help.png)

When you run it the first time, you won't have a token yet.
Enter your username and password. The script will spit out
your token for future use. Once you have your token, you can 
run the app again in the future using that (recommended).

Here's an example run:

![example run for the demo](https://github.com/rizwanjiwan/anovaphp/raw/main/demo.png)

If you want to use the library for your own apps the basic usage is:

1. Create an AnovaClient instance either with `$client = new AnovaClient(..)` if you have a token or `$client = AnovaClient.fromEmailAndPassword(...)` if you don't.
2. Use the `$info = $client->getInfo()` to get the current state of the Anova.
3. Update the `$info` object to whatever new state you want and run `$client->setInfo($info)` to tell the Anova to do its thing.

Take a look at the demo app for an example. The part you care about is probably just inside the main() method.
