function askfirst(text, url)
{
	var answer = window.confirm(text);

	if (answer) {
		window.location = url;
	}
}

//provide a random timestamp with each call to foil caching
function getTimestamp()
{
	var t = new Date();
	var r = "" + t.getFullYear() + t.getMonth() + t.getDate() + t.getHours() + t.getMinutes() + t.getSeconds();

	return(r);
}