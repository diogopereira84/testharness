var sessionWarningTimer = 0,
    sessionWarningTime = 0,
    sessionOutTimer = 0,
    sessionCountDownTime = 0,
    startTime = 0;
self.addEventListener('message', function(event) {
  switch (event.data.command) {
    case 'RUN_SESSION_TIMEOUT':
      sessionWarningTime = event.data.sessionWarningTime * 1000;
      sessionWarningTimer = setTimeout(sessionWarningCallBack, sessionWarningTime);
      sessionCountDownTime = event.data.sessionCountDownTime;
      break;
    case 'RESET_SESSION_TIMEOUT':
      clearTimeout(sessionWarningTimer);
      sessionWarningTimer = setTimeout(sessionWarningCallBack, sessionWarningTime);
      break;
    case 'CLEAR_SESSION_INTERVAL':
      clearInterval(sessionOutTimer);
      break;
  }
});
function sessionWarningCallBack () {
  var formattedTime = convertTimeToMinSeconds(sessionCountDownTime);
  self.postMessage({
    command: 'OPEN_SESSION_MODAL',
    formattedTime: formattedTime
  });
  startTime = new Date().getTime();
  sessionOutTimer = setInterval(sessionOutCallBack, 1000);
}
function sessionOutCallBack () {
  var timeDifference = parseInt((new Date().getTime() - startTime) / 1000),
      timeElapsed = sessionCountDownTime - timeDifference;
  if (timeElapsed >= 0) {
    var formattedTime = convertTimeToMinSeconds(timeElapsed);
    self.postMessage({
      command: 'UPDATE_SESSION_TIMER',
      formattedTime: formattedTime
    });
  } else {
    self.postMessage({
      command: 'SESSION_TIMED_OUT'
    });
    clearInterval(sessionOutTimer);
  }
}
function convertTimeToMinSeconds(timeInSeconds) {
  var timeOutMinutes = ~~(timeInSeconds / 60),
      timeOutSeconds = timeInSeconds % 60;
  if(timeOutSeconds < 10) {
      timeOutSeconds = '0' + timeOutSeconds;
  }
  return (timeOutMinutes + ':' + timeOutSeconds);
}
