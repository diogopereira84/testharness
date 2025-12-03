/* RSAES-OAEP.js
 * JavaScript Implementation of PKCS #1 v2.2 RSA CRYPTOGRAPHY STANDARD (RSA Laboratories, June 14, 2002)
 * Portions Copyright (C) Ellis Pritchard, Guardian Unlimited 2003.
 * Portions Copyright (C) Freedompay, Inc. 2017.
 * Distributed under the BSD License.
 */

// Pre-requisites: 
// JavaScript resources which should be included before this source file:
// jsbn.js (http://www-cs-students.stanford.edu/~tjw/jsbn/)
// rusha.js (https://github.com/srijs/rusha)

/**
* Make sure jsbn.js and rusha.js is loaded first
*/

define([
  "exports",
   "Fedex_Pay/js/view/jsbn",
   "Fedex_Pay/js/view/rusha",
], function (exports, jsbn, rusha) {
  var sha1 = new Rusha();
	
	// new RsaOaep(modulus, exponent)
	function RsaOaep(n, e)
	{
		var k = n.length;
		if (n.charCodeAt(0) == 0) { k--; }
		
		var n_buffer = Buffer.fromBinaryString(n);
		
		this.n = Buffer.toBigint(n_buffer);
		//this.e = new BigInteger(e);
		this.e = e; // jsbn modPowInt works off a native integer
		this.k = k;
	}
	
	// INTERNAL BUFFER FORMAT: byte arrays
	var ByteArrayBuffer = {
			empty: [],
			const_0x01: [0x01],
			zero: function(len) {
					var buffer = new Array(len);
					for (var i =0; i<len; i++) { buffer[i] = 0; }
					return buffer;
			},
			fromBinaryString: function(str) {
					var len = str.length;
					var buffer = new Array(len);
					for (var i = 0; i < len; i++) {
						buffer[i] = str.charCodeAt(i);
					}
					return buffer;
			},
			fromByteArray: function(byte_array) {
					return byte_array;
			},
			fromArrayBuffer: function(array_buffer) {
				return ByteArrayBuffer.fromUint8Array( new Uint8Array(array_buffer) );
			},
			fromUint8Array: function(bytes) {
				var len = bytes.byteLength;
				var buffer = new Array(len);
				for (var i = 0; i < len; i++) {
					buffer[i] = bytes[i];
				}
				return buffer;
			},
			toBigint: function(buffer) {
				// jsbn.js's BigInteger implementation can work straight from an array of byte values
				return new jsbn(buffer);
			},
			xor: function(buf1, buf2) {
					var len = buf1.length;
					if (len != buf2.length) throw 'Attempt to XOR two differently-sized buffers';
					var xbuf = new Array(len);
					for (var i = 0; i < len; i++) {
						xbuf[i] = buf1[i] ^ buf2[i];
					}
					return xbuf;
			},
			concat: Array.concat 
								? Array.concat 		// Firefox has static Array.concat, but Chrome does not
								: function() { return Array.prototype.concat.apply([], arguments); }
			,
			slice: function(buffer, start, end){
					if (start == 0 && end == buffer.length) return buffer;
					return buffer.slice(start, end);
			},
			hexdump: function(buffer) {
				var tmp = new Array(buffer.length + Math.ceil(buffer.length / 16));
				for (var i = 0; i < buffer.length; i++) {
					if (i > 0 && ((i % 16) == 0)) { tmp[tmp.length] = '\n'; }
					tmp[tmp.length] = ('00' + buffer[i].toString(16)).slice(-2);
				}
				return tmp.join("");
			},
	};
			
	var Buffer = ByteArrayBuffer;
		
	var getRandomData;
	// if you want to add support for new entropy sources, here is the place
	if ((typeof window.crypto !== 'undefined' && typeof window.crypto.getRandomValues !== 'undefined')||(typeof window.msCrypto !== 'undefined' && typeof window.msCrypto.getRandomValues !== 'undefined'))
		getRandomData = function(len) {
			var buffer = new Uint8Array(len);
			var tempCrypto = window.crypto || window.msCrypto;
			tempCrypto.getRandomValues(buffer);
			return ByteArrayBuffer.fromUint8Array(buffer);
		};
	else
		throw 'Could not find a viable entropy source';
	
	/* rsa_hash must be a function that takes a buffer object (one whose character codes are raw byte values),
	 * and returns a new buffer object containing the hash.
	 */
	var rsa_hash = function(s) {
		var raw_digest = sha1.rawDigest(s);
		return Buffer.fromArrayBuffer(raw_digest.buffer);
	};

	var empty_L_hash = rsa_hash("");
	var hLen = empty_L_hash.length;
	
	// perform RSA MGF1 mask generation function
	var mgf1 = function(mfgSeed, maskOctetLen) {
        var T = Buffer.empty;
        var C,h;
        var seed = (mfgSeed);
        for (var c=0; c< maskOctetLen/hLen+1; c++) {
				var C = [ ((c&0xff000000) >> 24),
						  ((c&0x00ff0000) >> 16),
						  ((c&0x0000ff00) >> 8),
						   (c&0x000000ff)
					    ];
                h = rsa_hash(Buffer.concat(seed, C));
                T = Buffer.concat(T, h);
        }
		return Buffer.slice(T, 0,maskOctetLen);
	}

	RsaOaep.prototype.eme_oaep_encode = function(m) {
		var k = this.k;
		
		var MGF = mgf1;
		
		// 1. Length checking:
		// (b)
		if (m.length > k - 2 * hLen - 2)
			throw 'Message  too long';
		
		// 2. EME-OAEP encoding:
		// (a)
		var lHash = empty_L_hash;
		// (b) generate octet string consisting of @k - @mLen - 2*@hLen - 2 zero octets
		var PS = Buffer.zero(k - m.length - 2 * hLen - 2);
		// (c) concatenate @lHash, @PS, 0x01, and @M to form a data block @DB of length k - hLen - 1
		var DB = Buffer.concat(lHash, PS, Buffer.const_0x01, m);
		// (d) generate a random octet string @seed of length @hLen
		var seed = getRandomData(hLen);
		// (e) Let @dbMask = MGF(@seed, @k - @hLen - 1)
		var dbMask = MGF(seed, k - hLen - 1);
		// (f) Let @maskedDB = @DB xor @dbMask
		var maskedDB = Buffer.xor(DB, dbMask);
		// (g) Let @seedMask = MGF(@maskedDB, hLen)
		var seedMask = MGF(maskedDB, hLen);
		// (h) Let @maskedSeed = @seed xor @seedMask
		var maskedSeed = Buffer.xor(seed, seedMask);
		// (i) Concatenate (note: spec says prefix with 0x00; because RSA involves treating the plaintext as a number, this has no effect.)
		var EM = Buffer.concat(maskedSeed, maskedDB);
		// there you go! that's what you're encrypting.
		return EM;
	};
	
	var bigintToBinaryString = function(bi){
		// inefficient, but this won't be getting called often
		var bi_str = bi.toString(16);
		if (bi_str.length % 2 == 1) { bi_str = '0' + bi_str; } // ugh
		var bi_array = new Array(bi_str.length / 2);
		for (var i = 0, j = 0; i < bi_str.length - 1; i+=2, j++) {
			var b = parseInt(bi_str.substr(i, 2), 16);
			bi_array[j] = String.fromCharCode(b);
		}
		return bi_array.join("");
	}
	
	RsaOaep.prototype.encrypt = function(m) {
	
		var m_buffer = Buffer.fromBinaryString(m);
		
		var mp_buffer = this.eme_oaep_encode(m_buffer);
		
		var mp_bigint = Buffer.toBigint(mp_buffer);
		
		var C_bigint = mp_bigint.modPowInt(this.e, this.n);
		
		var C_str = bigintToBinaryString(C_bigint);
		
		var C_b64 = window.btoa(C_str);

		return C_b64;		
	}
	
	return RsaOaep;

});
