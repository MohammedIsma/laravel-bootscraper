@extends("layouts.layout-main")

@section("Content")
<h2>Bootscraper v1.0</h2>
<div class="row">
    <div class="col-md-7">
        <div class="panel panel-primary">
            <div class="panel-heading">Layout generated</div>
            <div class="panel-body" style="background-color: #fff;color:#333;">
                <p>
                    Thank you for using <strong>Bootscraper v1.0</strong>, I hope your template looks good.
                </p>
                <p>
                    Your main layout file is saved in <strong>resources</strong> > <strong>views</strong> > <strong>layouts</strong> > <strong>layout-{{ \Config::get("bootscraper.layout_name") }}.blade.php</strong>
                </p>
                <p>
                    To edit the menu go <strong>resources</strong> > <strong>views</strong> > <strong>menus</strong> > <strong>menu-{{ \Config::get("bootscraper.layout_name") }}.blade.php</strong>
                </p>
                <p>"Go build something awesome"</p>
                <a target="_blank" href="http://twitter.com/mohammedisma" class="btn btn-success">Yes, template works (say, Hi)</a>
                <a target="_blank" href="https://github.com/MohammedIsma/laravel-bootscraper/issues" class="btn btn-warning">Nope, I have an issue to report</a>
                
                <br /><br />
                <iframe src="https://ghbtns.com/github-btn.html?user=MohammedIsma&repo=laravel-bootscraper&type=star&count=true&size=large" frameborder="0" scrolling="0" width="160px" height="30px"></iframe>
                
            </div>
        </div>
    </div>
</div>
@endsection