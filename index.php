<?php
/*
  PlaceEarth by Christian Heilmann
  Version: 1.0
  Homepage: http://isithackday.com/hacks/placeearth/
  Copyright (c) 2009, Christian Heilmann
  Code licensed under the BSD License:
  http://wait-till-i.com/license.txt
*/
error_reporting(0);

if(isset($_GET['url'])){
  
  //TODO: Fallback for non-JS version, be creative!
  
}

if(isset($_GET['url'])){

  $key = 'RnqsLdPV34FbBSqnpSS8YMGcPcO9HRtWgAQ_Y7dN6pjCjF8ldmk_I.IrxtOOPZc-';

// ^ placemaker key, please replace with your own! 

  $url = $_GET['url'];
  $o = $_GET['output'];

// load the HTML page using YQL to filter the HTML and fix any UTF-8 
// nasties 
  
  $realurl = 'http://query.yahooapis.com/v1/public/yql?q=select%20*%20'.
             'from%20html%20where%20url%20%3D%20%22'.
             urlencode($url).'%22&format=xml';
  $ch = curl_init(); 
  curl_setopt($ch, CURLOPT_URL, $realurl); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $c = curl_exec($ch); 
  curl_close($ch);
  if(strstr($c,'<')){
    $c = preg_replace("/.*<results>|<\/results>.*/",'',$c);
    $c = preg_replace("/<\?xml version=\"1\.0\"".
                      " encoding=\"UTF-8\"\?>/",'',$c);
    $c = strip_tags($c);
    $c = preg_replace("/[\r?\n]+/"," ",$c);
    if(isset($_GET['raw'])){
      echo "<h1>Content</h1>\n\n".htmlentities($c);
    }

// Post the result to Placemaker

    $ch = curl_init(); 
    if(isset($_GET['lang'])){
      $lang = '&inputLanguage='.$_GET['lang'];
    }
    define('POSTURL', 'http://wherein.yahooapis.com/v1/document');
    define('POSTVARS', 'appid='.$key.'&documentContent='.urlencode($c).
                    '&documentType=text/plain&outputType=xml'.$lang);
  
    $ch = curl_init(POSTURL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, POSTVARS);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    $placemaker = curl_exec($ch);
    curl_close($ch);

    if(isset($_GET['raw'])){
      echo "<h1>Placemaker</h1>\n\n<pre>".htmlentities($placemaker)."</pre>";
    };
  
// turn into an PHP object and loop over the results

    $placelist = '<ul id="locations">';
    $places = simplexml_load_string($placemaker, 'SimpleXMLElement',
                                    LIBXML_NOCDATA);    
    $out = '';
    if($places->document->placeDetails){
      $foundplaces = array();

// create a hashmap of the places found to mix with the references found

      foreach($places->document->placeDetails as $p){
        $wkey = 'woeid'.$p->place->woeId;
        $foundplaces[$wkey]=array(
          'name'=>str_replace(', ZZ','',$p->place->name).'',
          'type'=>$p->place->type.'',
          'woeId'=>$p->place->woeId.'',
          'lat'=>$p->place->centroid->latitude.'',
          'lon'=>$p->place->centroid->longitude.''
        );
      }
    
// loop over references and filter out duplicates

      $refs = $places->document->referenceList->reference;
      $usedwoeids = array();
      foreach($refs as $r){
        foreach($r->woeIds as $wi){
          if(in_array($wi,$usedwoeids)){
            continue;
          } else {
            $usedwoeids[] = $wi.'';
          }
          $currentloc = $foundplaces["woeid".$wi];
          if($r->text!='' && $currentloc['name']!='' && 
            $currentloc['lat']!='' && $currentloc['lon']!=''){
            $text = preg_replace('/\s+/',' ',$r->text);
            $name = addslashes(str_replace(', ZZ','',$currentloc['name']));
            $desc = addslashes($text);
            $lat = $currentloc['lat'];
            $lon = $currentloc['lon'];
            $kml[] = '<Placemark>'.
                     '<name>'.$name.'</name>'.
                     '<description>'.$desc.'</description>'.
                     '<Point>'.
                     '<coordinates>'.$lon.','.$lat.',0</coordinates>'.
                     '</Point>'.
                     '</Placemark>';
                     $class = stripslashes($desc)."|$name|$lat|$lon";
            $placelist.= "<li>".
                         "<button class=\"$class\">".
                         "<span class=\"vcard\">\n<span class=\"adr\">\n".
                         "<span class=\"locality\">".stripslashes($desc).
                         " ($name)</span>\n".
                         "</span>\n<span class=\"geo\">()\n".
                         "<span class=\"latitude\">$lat</span>,\n".
                         "<span class=\"longitude\">$lon</span>". 
                         "\n)</span>\n</span></button></li>";
          }
        }
      }
     if($o=='kml'){
       $kml = implode("\n",$kml);
       header('content-type:application/vnd.google-earth.kml+xml');
       header('Content-disposition: attachment; filename=locations.kml');
       echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
       echo '<kml xmlns="http://www.opengis.net/kml/2.2"><Document>'.$kml;
       echo '</Document></kml>';
     }
   }
   $placelist .= '</ul>';
  }
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
 "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">  
  <title></title>
  <link rel="stylesheet" href="http://yui.yahooapis.com/2.7.0/build/reset-fonts-grids/reset-fonts-grids.css" type="text/css">
<script type="text/javascript" src="http://yui.yahooapis.com/combo?2.8.0r4/build/yahoo/yahoo-min.js&2.8.0r4/build/event/event-min.js"></script> 
  <style type="text/css">
  html,body{background:#cc9;}
  #doc2{
  background:#fff;
  border:1em solid #fff;
  }
  h1{color:#369;font-size:200%;font-family:calibri,sans-serif;font-weight:bold;padding-bottom:.5em}
  h2{color:#036;font-size:120%;font-family:calibri,sans-serif;font-weight:bold;padding:.5em 0}
  p{padding-bottom:.5em;}
  form{
  background:#69c;
  padding:1em;
  font-weight:bold;
  -moz-border-radius:5px;
  }
  #map3d{width:100%;height:400px;}
      .geo{position:absolute;top:0;left:-9999px;}

      button{
        border:none;
  background:transparent;
        display:block;
        width:100%;
        text-align:left;
      }
      ul{
        margin:0;
        padding:0;
        list-style:square;
      }
  #locations{overflow:auto;height:400px;}
      li{
        color:#000;
        border-top:1px solid #fff;
        list-style-type:none;
        padding:5px;
        margin:0;
        background:#ccc;
      }
      li:hover{
        background:#69c;
  color:#fff;
      }
  li:hover button{color:#fff;}
  </style>
</head>
<body>
<div id="doc2" class="yui-t7">
  <div id="hd" role="banner"><h1>PlaceEarth</h1></div>
  <div id="bd" role="main">
    <p>PlaceEarth is a demo of how you can use Yahoo Placemaker to find geographical locations in a certain web site and show them in Google Earth.</p>
    <p>Simply enter the URL of the site you want to analyse in the following form.</p>
      <form action="kml.php" method="get">
        <div><label for="url">URL:</label><input type="text" name="url" id="url" value="<?php echo $_GET['url'];?>"><input type="submit" value="get locations"></div>
      </form>
      <?php if(isset($_GET['url'])){?>
      
      <?php if($placelist!=''){?>
       <div class="yui-gc">
         <div class="yui-u first"><h2>Explore on Google Earth:</h2>
         <div id="map3d"></div></div>
         <div class="yui-u"><h2>Locations found:</h2>
         <?php echo $placelist;?></div>
       </div>
      <?php }else{?>
        <h2 class="warn">Couldn't find any locations for this URL :-(</h2>
      <?php }}?>
      <h2>API (of sorts)</h2>
      <p>If you want to see the outcome directly in Google Earth as KML, simply add an <code>output=kml</code> to the URL. For example: <br><a href="index.php?url=http%3A%2F%2Fwait-till-i.com&output=kml">index.php?url=http%3A%2F%2Fwait-till-i.com&output=kml</a></p>
      <p>To debug and see why nothing was found add <code>raw=true</code> to the URL. For example: <br><a href="index.php?url=http%3A%2F%2Fwait-till-i.com&raw">index.php?url=http%3A%2F%2Fwait-till-i.com&raw=true</a></p>
  </div>
  <div id="ft" role="contentinfo"><p>Written by Christian Heilmann, source available on GitHub</p></div>
</div>

<?php if(isset($_GET['url'])){?>

<script src="http://www.google.com/jsapi?key=ABQIAAAA1XbMiDxx_BTCY2_FkPh06RRaGTYH6UMl8mADNa0YKuWNNa8VNxQEerTAUcfkyrr6OwBovxn7TDAH5Q"></script>
<script type="text/javascript">
var ge;
google.load("earth", "1");

earth = function(){
  
  function flyTo(e){
    YAHOO.util.Event.preventDefault(e);
    
    var t = YAHOO.util.Event.getTarget(e); 
    while(t.nodeName.toLowerCase()!=='button'){
      t = t.parentNode;
    }
    if(t.nodeName.toLowerCase()==='button'){
      var chunks = t.className.split('|');
      var desc = chunks[0];
      var name = chunks[1];
      var lat = +chunks[2]
      var lon = +chunks[3];
      var lookAt = ge.createLookAt('');
      var placemark = ge.createPlacemark('');
      placemark.setDescription(desc + ' (' + name + ')');
      var point = ge.createPoint('');
      point.setLatitude(lat);
      point.setLongitude(lon);
      point.setAltitudeMode(ge.ALTITUDE_CLAMP_TO_GROUND);
      placemark.setGeometry(point);
      ge.getFeatures().appendChild(placemark);
      var balloon = ge.createFeatureBalloon('');
      balloon.setFeature(placemark);
      balloon.setMinWidth(400);
      ge.setBalloon(balloon);      
      lookAt.set(lat, lon, 10, ge.ALTITUDE_RELATIVE_TO_GROUND,
                 0, 60, 20000);
      ge.getView().setAbstractView(lookAt);
    }

  }  
  function initInstance(instance) {
    ge = instance;
    ge.getWindow().setVisibility(true);
    ge.getNavigationControl().setVisibility(ge.VISIBILITY_AUTO);
    ge.getLayerRoot().enableLayerById(ge.LAYER_BORDERS, true);
    ge.getLayerRoot().enableLayerById(ge.LAYER_ROADS, true);
    YAHOO.util.Event.addListener('locations','click', earth.flyTo);
  }
  function failureInstance(errorCode) {
    alert('Ooops: '+errorCode);
  }
  return{ic:initInstance,fc:failureInstance,flyTo:flyTo}
}();
window.onload = function(){
  google.earth.createInstance('map3d', earth.ic, earth.fc);
}
    </script>
<?php } ?>    
</body>
</html>
    
