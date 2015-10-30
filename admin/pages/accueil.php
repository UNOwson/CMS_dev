<?php
	$reports = Db::Get('select count(*) from {reports} where deleted = 0 or deleted is null');
?>
<?php
    $os_name = php_uname('s');
    $os_info = array (
        "Windows NT" => "<i class='fa fa-lg fa-windows'></i>",
        "Linux" => "<i class='fa fa-lg fa-linux'></i>",
        "Apple" => "<i class='fa fa-lg fa-apple'></i>",
    );
    $dbv = Db::ServerVersion();
    $phpv = phpversion();
    $dbversion = strstr($dbv, '-', true);
    $phpversion = strstr($phpv, '-', true);
?>
  
    <div class="panel panel-default">
      <div class="a-panel-heading" style="background-color: rgb(65, 69, 72);color: white;">
        <h3 class="panel-title">Informations Généraux</h3>
      </div>
      <div class="panel-body">
        <div id="charts">
          <div id="chart" class="mysql">
            <p class="info"><?php echo $dbversion; ?></p>
            <p class="title"><strong>MySQL</strong></p>
          </div>
          <div id="chart" class="php">
            <p class="info"><?php echo phpversion(); ?></p>
            <p class="title"><strong>PhP</strong></p>
          </div>
          <div id="chart" class="version">
            <p class="info"><?php echo EVO_VERSION ?></p>
            <p class="title"><strong>Version CMS</strong></p>
          </div>
          <div id="chart" class="build">
            <p class="info">#<?php echo EVO_REVISION ?></p>
            <p class="title"><strong>Build CMS</strong></p>
          </div>
          <div id="chart" class="dbv">
            <p class="info">#<?php echo DATABASE_VERSION ?></p>
            <p class="title"><strong>Rev. DB</strong></p>
          </div>
          <div id="chart" class="os">
            <p class="info" style="margin: 10px 0px 5px;"><?php echo $os_info[$os_name]; ?></p>
            <p class="title"><strong>OS</strong></p>
          </div>
          <div id="chart" class="members">
            <p class="info"><?php echo Db::Get('select count(*) from {users}') ?></p>
            <p class="title"><strong>Membres</strong></p>
          </div>
          <div id="chart" class="visitors">
            <p class="info"><?php  $hits = file_get_contents("./hits.txt"); $hits = $hits + 1; $handle = fopen("./hits.txt", "w"); fwrite($handle, $hits); fclose($handle); print $hits ?></p>
            <p class="title"><strong>Visiteurs</strong></p>
          </div>
        </div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="a-panel-heading">
        <h3 class="panel-title">Statistiques Et Données</h3>
      </div>
      <div class="panel-body">
        <div id="stats_data">
          <div class="subsd">
            <legend><i class="fa fa-lg fa-list-alt"></i> Forum</legend>
            <ul>
              <li><strong>Nombre de sujets :</strong> <?php echo (Db::Get('select count(*) from {forums_topics}')) ?></li>
              <li><strong>Nombre de messages :</strong> <?php echo (Db::Get('select count(*) from {forums_posts}')) ?></li>
            </ul>
          </div>
          <div class="subsd">
            <legend><i class="fa fa-lg fa-pencil"></i> Contenu</legend>
			<ul>
              <li><strong>Nombre d'article :</strong> <?php echo (Db::Get('select count(*) from {pages}')) ?></li>
              <li><strong>Nombre de publication :</strong> <?php echo (Db::Get('select count(*) from {pages} where pub_date > 0')) ?></li>
              <li><strong>Nombre de révision :</strong> <?php echo (Db::Get('select count(*) from {pages_revs}')) ?></li>
            </ul>
          </div>
          <div class="subsd">
            <legend><i class="fa fa-lg fa-users"></i> Membres</legend>
            <ul>
              <li><strong>Nombre de membre :</strong> <?php echo Db::Get('select count(*) from {users}') ?></li>
              <li><strong>Nombre de banni :</strong> <?php echo Db::Get('select count(*) from {banlist}') ?></li>
              <li><strong>Membre connecté :</strong> N/D</li>
              <li><strong>Nombre de visiteur :</strong> <?php print $hits ?></li>
              <li><strong>Dernier inscript :</strong> Dark_fire_angel</li>
            </ul>
          </div>
          <div class="squota">
            <legend><i class="fa fa-lg fa-cloud"></i> Espace Disque</legend>
			<ul>
              <li><strong>Nombre de fichier :</strong> <?php echo plural('%count%', Db::Get('select count(*) from {files}')) ?></li>
              <li><strong>Espace utilisé :</strong> <?php echo plural('%count%', mk_human_unit(Db::Get('select sum(size) from {files}'))) ?></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="a-panel-heading">
      	<h3 class="panel-title">À Propos de ...</h3>
	  </div>
      <div class="panel-body">
        <div class="tabbable">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab1" data-toggle="tab">Développement</a></li>
            <li><a href="#tab2" data-toggle="tab">Crédits</a></li>
            <li><a href="#tab3" data-toggle="tab">License</a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab1">
              <table class="table table-hover"> 
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Pseudo</th>
                    <th>Rôle</th>
                    <th>Email</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">1</th>
                    <td>Yan Bourgeois</td>
                    <td>Coolternet</td>
                    <td>Chef du projet / Designer</td>
                    <td>dev@evolution-network.ca</td>
                  </tr>
                  <tr>
                    <th scope="row">2</th>
                    <td>Alex Duchesne</td>
                    <td>Alexus</td>
                    <td>Développeur en chef</td>
                    <td>alex@alexou.net</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="tab-pane" id="tab2">
            <h3>Librairies utilisées par EVO-CMS:</h3>
            ===<br>
	            <a href="http://raymondhill.net/blog/?p=441">FineDiff</a> - MIT<br>
	            <a href="http://parsedown.org">Parsedown</a> - MIT<br>
	            <a href="https://github.com/clouddueling/mysqldump-php">MySQLDump</a> - MIT<br>
	            <a href="http://maxmind.com">GeoIP</a> - LGPL<br>
	            <a href="http://www.adminer.org/">Adminer</a> - Apache License<br>
	            <br>
	            <a href="http://jquery.com">jQuery</a> - MIT<br>
	            <a href="http://getbootstrap.com">Bootstrap</a> - MIT<br>
	            <a href="http://ckeditor.com/">ckeditor</a> - MPL<br>
	            <a href="http://fancyapps.com/fancybox">fancybox</a> - MIT<br>
	            <a href="http://www.sceditor.com/">sceditor</a> - MIT<br>
	            <a href="http://markitup.jaysalvat.com/">markitup</a> - MIT<br>
	            <a href="http://highcharts.com/">Highcharts</a> - CC BY-NC 3.0<br>
	            <br>
	            <a href="http://fortawesome.github.io/Font-Awesome/">Font-Awesome</a> - SIL OFL 1.1<br>
	            <a href="http://www.famfamfam.com/lab/icons/silk/">famfamfam - Silk</a> - CC BY 2.5<br>
	            Nomicons - CC BY 2.5<br>
            </div>
            <div class="tab-pane" id="tab3">
              <h1><legend>License MIT</legend></h1>
              <p>Evo-CMS : A Small Content Management System With Community Features</p>
              <p>Copyright (c) 2014 Alex Duchesne alex@alexou.net 2014 Yan Bourgeois dev@evolution-network.ca</p>
              <p>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:</p>
              <p>The above copyright notice and this permission notice shall be included in all copies or substantial portions of The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.</p>
              <p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>
              <p></p>
            </div>
          </div>
        </div>
      </div>
    </div>