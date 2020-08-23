

// Found @ https://stackoverflow.com/a/31751351
$.fn.serializeObject = function(){
  var o = {};
  var a = this.serializeArray();
  $.each(a, function() {
    if (o[this.name] !== undefined) {
      if (!o[this.name].push) {
        o[this.name] = [o[this.name]];
      }
      o[this.name].push(this.value || '');
    } else {
      o[this.name] = this.value || '';
    }
  });
  return o;
};

// Set a jquery object's value to be the current TZ
// Probably add validation?
$.fn.setLocalTimezone = function() { 
  var zone = /(GMT[\+\-]\d+)/.exec(new Date().toString())[1];
  this.each(function() {
    $(this).val(zone);
  });

  return this;
}

// Adapted from: 
//   https://stackoverflow.com/a/47065313/9238801
// Post a form
// Body is encoded as requestURI, not json
window.post = function(url, data) {
  return fetch(
    url, {
      method: "POST", 
      body: encodeRequestURI(data),
      headers: {
        //'Content-Type' : 'application/json'
        // May wish to request json on response using "Accept" when response is implemented
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });
}

// Post a form asynchronously / without refreshing page
// Append the post to the list when you're done
// Probably worth modifying this to insert the new post in 
// the correct location based on timestamp, maybe
// Validation, or response handling may be useful. 
// ( Assuming the server implments submission POST responses)
postAppend = function() {
  var values = $(this).serializeObject();

  var payload = values;
  payload["submit"] = 1;
  payload["sendreplies"] = 1;

  window.post(
    "submit",
    payload
  );

  // Append this to the list
  var p = $(this).prev().clone();

  p.prependTo($(this));

  p.find("[name='subreddit']").text(payload['subreddit']);
  p.find("[name='title']").text(payload['title']);
  p.find("[name='when']").text(payload['whentime'] + payload['whendate']);

  return false;
}

// Found at https://stackoverflow.com/a/6566471/9238801
encodeRequestURI = function(obj) {
  var str = "";
  for (var key in obj) {
    if (str != "") {
      str += "&";
    }
    str += key + "=" + encodeURIComponent(obj[key]);
  }
  return str;
}
