/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
try {
	document.cookie = 'FXOGUID' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'Bearer' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'FXOLBSESSIONID' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'FXOSESSIONID' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	document.cookie = 'NEXTGENJID' +'=; Path=/; Domain=.fedex.com; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
} catch (err) {
	console.log(err);
}
