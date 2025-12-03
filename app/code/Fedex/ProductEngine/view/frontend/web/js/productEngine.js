define(["require","exports"], function (require, exports) {
(function (exports, factory) {
    (factory(exports));
}(this, (function (exports) {

(function() {
	/**
	 * Limited implementation of some core java.util classes.
	 * These were created to support direct conversion of java code to javascript.
	 * The STJS library used for the conversion did not natively support usage of 
	 * the java.util package because javascript does not have any "collections" constructs.
	 * Any additional methods for the existing types or additional classes from java.util
	 * should be added as needed.
	 */
	
	function Collections() {};
	Collections.unmodifiableSet = function(set) {
		return set; // Cant do this is javascript easily, just here for conversion from Java
	};
	window.Collections = Collections;
	

	function ArrayIterator(values) {
		this.values=values;
		this.index=-1;
	}
	ArrayIterator.prototype.next = function() {
		this.index++;
		return this.values[this.index];
	};
	ArrayIterator.prototype.hasNext = function() {
		return this.values.length>this.index+1;
	};
	window.ArrayIterator = ArrayIterator;
	
	/*
	 * IdSet expects that all elements have getId method which returns a unique number which can be used as a key
	 */
	function ArraySet() {
        this.elementIds = {};
		this.elements = [];
	}
	ArraySet.prototype.iterator = function() {
		return new ArrayIterator(this.elements);
	};
	ArraySet.prototype.get = function(id) {
        var element = null; 
        if(id!=null) {
            return this.elementIds[id];
        }
        return element;
    };
    ArraySet.prototype.addAll = function(otherSet) {
       var it = otherSet.iterator();
       while(it.hasNext()) {
    	   this.add(it.next());
       }
	};
	ArraySet.prototype.addArray = function(a) {
		for(var i=0;i<a.length;i++) {
			this.add(a[i]);
		}
	};
    ArraySet.prototype.add = function(element) {
    	if(!this.contains(element)) {   	
	        var id = this.getIdOfElement(element);
	        if(id!=null) {
	            this.elements.push(element);
	            this.elementIds[id]=element;
	        } else {
				console.log('(WARN) ArraySet.add: Tried to add element '+element+' but it did not have an id. Ignoring.');
			}
    	}
	};
	ArraySet.prototype.remove = function(element) {
        var id = this.getIdOfElement(element);
        if(id!=null) {
            this.elementIds[id] = null;
            for(var i=0;i<this.elements.length;i++) {
				if(this.elements[i]===element) {
					this.elements.splice(i,1);
					break;
				}
            }
        }
	};
	ArraySet.prototype.moveElementUp = function(element) {
		if(this.contains(element)) {
			for(var i=0;i<this.elements.length;i++) {
				if(i>0 && this.elements[i]===element) {
					this.elements.splice(i-1, 0, this.elements.splice(i, 1)[0]);
					break;
				}
			}
		}
	};
	ArraySet.prototype.contains = function(element) {
        var id = this.getIdOfElement(element);
        if(id==null)
            return false;
		return this.elementIds[id]!=null;
	};
	ArraySet.prototype.getIdOfElement = function(element) {
		var id = null; 
        if(typeof element.getId === 'function') {
            id = element.getId();
        } else if(typeof element === 'number' || typeof element === 'string') {
        	id = element;
        }
        if(id==null && typeof element.id != "undefined")
            id = element.id;
        return id;
	};
	ArraySet.prototype.isEmpty = function() {
		return this.elements.length<1; 
	};
	ArraySet.prototype.size = function() {
		return this.elements.length; 
	};
	ArraySet.prototype.clear = function() {
		this.elementIds = [];
		this.elements = [];
	};
	ArraySet.prototype.toJSON = function() {
		return ['ArraySet',this.elements];
	};
	window.ArraySet = ArraySet;
	
	
	function ArrayMap() {
	    this.valsByKey = {};
		this.size = 0;
	}
	ArrayMap.prototype.get = function(key) {
		var value = null; 
        if(key!=null) {
            value = this.valsByKey[key];
        }
        return value;
	};
	ArrayMap.prototype.put = function(key,value) {
		if(!this.containsKey(key))
			this.size++;
        this.valsByKey[key]=value;
	};
	ArrayMap.prototype.remove = function(key) {
		if(this.containsKey(key))
			this.size--;
	    this.valsByKey[key]=null;
	};
	ArrayMap.prototype.values = function() {
		var list = new ArrayList();
		for (var i in this.valsByKey) {
            if (this.valsByKey[i]!=null) {
                list.add(this.valsByKey[i]);
            }
        }
		return list;
	};
	ArrayMap.prototype.containsKey = function(key) {
		return this.valsByKey[key]!=null;
	};
	ArrayMap.prototype.size = function() {
		return this.size; 
	};
	ArrayMap.prototype.clear = function() {
		this.valsByKey = {};
	};
	ArrayMap.prototype.keySet = function() {
		var list = new ArrayList();	
		for (var i in this.valsByKey) {
            if (this.valsByKey[i]!=null) {            	        
            	list.add(i);
            }
        }
		return list;
	};
	ArrayMap.prototype.isEmpty = function() {
		if(this.size >=1)
			return false;
		else
			return true;
	};
	ArrayMap.prototype.putAll = function(oArrayMap) {
		var oValsByKey = oArrayMap.valsByKey;
		for (var oKey in oValsByKey) {
            if (oValsByKey[oKey]!=null) {            	        
            	if(!this.containsKey(oKey))
        			this.size++;
                this.valsByKey[oKey]=oValsByKey[oKey];
            }
        }
	};
	window.ArrayMap = ArrayMap;

	
	function ArrayList() {
		this.values = [];
	}
	ArrayList.prototype.iterator = function() {
		return new ArrayIterator(this.values);
	};
	ArrayList.prototype.get = function(index) {
		if(this.isValidIndex(index))
			return this.values[index];
		return null;// TODO throw exception?
	};
	ArrayList.prototype.addAtIndex = function(index, value) {
		this.values.splice(index, 0, value);
	};
	ArrayList.prototype.add = function(value) {
        this.values.push(value);
	};
	ArrayList.prototype.addAll = function(otherList) {
        var it = otherList.iterator();
        while(it.hasNext()) {
     	   this.add(it.next());
        }
 	};
 	ArrayList.prototype.addArray = function(a) {
 		for(var i=0;i<a.length;i++) {
			this.add(a[i]);
		}
 	};
	ArrayList.prototype.remove = function(index) {
		if(this.isValidIndex(index))
			this.values.splice(index,1);
	};
	ArrayList.prototype.removeValue = function(value) {
		for(var i=0;i<this.values.length;i++) {
			if(this.values[i]==value) {
				this.remove(i);
				return;
			}
	    }
	};
	ArrayList.prototype.removeMatch = function(matchCb) {
		for(var i=0;i<this.values.length;i++) {
			if(matchCb(this.values[i])) {
				this.remove(i);
				return;
			}
	    }
	};
	ArrayList.prototype.clear = function() {
		this.values = [];
	};
	/*ArrayList.prototype.set = function(index,value) {
	      
    		if(index!=-1){
    			this.values[index]=value;
    		}
	};*/
	/*ArrayList.prototype.remove = function(value) {
		for(var i=0;i<this.values.length;i++) {
			if(this.values[i]==value) {
				this.values.splice(i,1);
				break;
			}
	    }
	};*/
	ArrayList.prototype.size = function() {
		return this.values.length; 
	};
	ArrayList.prototype.indexOf = function(value) {
		for(var i=0;i<this.values.length;i++) {
			if(this.values[i]==value) {
				return i;
			}
	    }
		return -1;
	};
	ArrayList.prototype.isEmpty = function() {
		return this.values.length<1; 
	};
	ArrayList.prototype.contains = function(value) {
		var index = this.indexOf(value);
		return index>-1;
	};
	ArrayList.prototype.isValidIndex = function(index) {
		return index>=0 && index<this.values.length;
	};
	ArrayList.prototype.toArray = function() {
		return this.values.slice(0);
	};
	ArrayList.prototype.toJSON = function() {
		return ['ArrayList',this.values];
	};
	ArrayList.prototype.removeAll = function(valueList) {
		var valueListArray = [];
		valueListArray = valueList.toArray();
		this.values = this.values.filter(item => !valueListArray.includes(item));
	 };
	window.ArrayList = ArrayList;
}());

// Depends on collections.js
// If STJS is available, adds toJSON to the enum 
(function() {
	var Utils = function(){};
	Utils.getExceptionMessage = function(ex) {
		if(typeof ex.getMessage === 'function') {
			ex.getMessage();
		} else if(ex.message != null) {
			return ex.message;
		} 
		return ex;
	};
	Utils.isStringsEqual = function(s1, s2) {
		if (s1 == null) {
			if(s2!=null)
				return false;
			else
				return true;
		} else if(s2 == null) {
			return false;
		}
		return s1 === s2;
	};
	Utils.addRangeToList = function(index, value, values) {
		values.addAtIndex(index, value);
	};
	Utils.addPageExceptionToList = function(index, value, values) {
		values.addAtIndex(index, value);
	};
	Utils.addIntegerToList = function(index, value, values) {
		values.addAtIndex(index, value);
	};
	
	Utils.convertToLongvalue = function(value) {
		return value;
	};
	Utils.convertToInteger = function(value) {
		return new Integer(value).valueOf();
	};
	Utils.convertToBoolean = function(value) {
		if (typeof value === 'boolean')
			return value;
		else 
			return ( value == 'true');
	};
	Utils.convertToFloat = function(value) {
		return new Number(value).valueOf();
	};
	Utils.isParsable = function(value){
		return !isNaN(value);
	};
	Utils.compareToStrings = function(thisValue, otherValue){
		if (otherValue == null)
			return 1;
		if (thisValue < otherValue)
			return -1;
		if (thisValue == otherValue)
			return 0;
		return 1;
	};
	Utils.getElementById = function(id,set) {
		return set.get(id);
	};
	Utils.validateFormat = function(type, value) {
		if(type == 'NUMERIC') {
			if(!value.match(/[^0-9]/))
				return true;
		}
		if(type == 'DECIMAL') {
			if(value.match(/^(\d+\.?\d{0,2}|\.\d{1,2})$/))
				return true;
		}
		if(type == 'ALPHA') {
			if(!value.match(/[^a-zA-Z]/))
				return true;
		}
		if(type == 'ALPHA_NUMERIC') {
			if(!value.match(/[^a-zA-Z0-9]/))
				return true; 
		}
		return false;
	};
	
	Utils.httpGet = function(uri, successCallback, errorCallback) {
		var xhr = new XMLHttpRequest();

		xhr.onreadystatechange = function(){
			if (xhr.readyState === 4) {
				if (xhr.status === 200) {
					var response = null;
					try {
						response = JSON.parse(xhr.response);
					} catch (e) {
						response = xhr.response;
					}
					successCallback(response, xhr.status);
				} else {
					errorCallback(xhr.responseText, xhr.status);
				}
			}
		};
		xhr.open('GET', uri);
		xhr.send(null);
	};

	Utils.restApiGet = function(uri, clientId, successCallback, errorCallback) {
		var responseStatus = null;
		fetch(uri, {
			method: 'GET',
			headers: {
		  		 'client_id': clientId
				}
		})				 
  		.then(response => {
    		// Check if the response is OK (status code 200-299)
			if (!response.ok) {
			// If not, throw an error to be caught by the catch block
				return response.json().then(errorData => {
					throw new Error(`Error: ${response.status} - ${errorData.message || 'Unknown error'}`);
				});
			}
			// Parse the response as JSON (response.json() returns a promise)
			responseStatus = response.status
			return response.json();
		})
		.then(data => {
			// This block is executed if the previous promise (response.json()) succeeds.
			successCallback(data, responseStatus);
		})
		.catch(error => {
			// This block handles any error that occurred during the fetch or parsing
			console.error('There was a problem with the fetch operation:', error);
			errorCallback(error.message, responseStatus);
		});

	};


	Utils.isEmptyArray = function(value) {
		return Array.isArray(value);
	}
	window.Utils = Utils;
	
	/**
	 * Add a toJSON method to all STJS enum types
	 */
	if(stjs) {
		if(stjs.enumEntry) {
			stjs.enumEntry.prototype.toJSON = function() {
				return this.name();
			};
		};
	}
	
	function JSONModelParser() {
		this.matchers = [];
		/** If false, an object will be passed to ALL overrides who's matchers return true for the keyvalue pair. 
		 * If false, it does not stop on the first match. */
		this.firstMatchOnly = false;
		
		// Parser configuration
		// Can be customized before using this parser
		this.attNameForType = '@type';
		this.attNameForClass = '@class';
		//this.setTypeOnNewClass = true;// Done during normal copy of properties, nothing needs explicitly copied
	}
	window.JSONModelParser = JSONModelParser;
	
	/**
	 * Add an override function and matcher to this parser. If none are configured, this parser will have the same functionality as calling JSON.parse with no specified reviver.
	 * Matchers will be evaluated in the same order that they were added to this parser. 
	 * 
	 * @param override function which will receive the key and value to modify and return the value after modification
	 * @param matcher function which returns true if the specified override should apply, false if it should not. 
	 * 			Params to the matcher will be key(member name) and value.
	 * 			If matcher is null, the override will not be added
	 * 	
	 */
	JSONModelParser.prototype.addOverride = function(matcher, override) {
		this.matchers.push({m:matcher,o:override});
	};
	JSONModelParser.prototype.addDefaultOverrides = function() {
		var thisRef = this;
		// Array to collections mapping
		this.addOverride(
				function(key,value){ return (value instanceof Array) /*&& (value.length>1);*/},
				function(key,value){
					//var className = value[0];
					var realObj = value;
					/*if(className=='java.util.HashSet' || className=='HashSet' || className=='ArraySet') {
						realObj = new ArraySet();
						realObj.addArray(value[1]);
					} else if(className=='java.util.ArrayList' || className=='ArrayList') {
						realObj = new ArrayList();
						realObj.addArray(value[1]);
					}*/
					realObj = new ArrayList();
					realObj.addArray(value);
					return realObj;
				}
		);
		
		// Generic @type & @class mapping
		this.addOverride(
				function(key,value){ 
					return (value&&(value[thisRef.attNameForType]||value[thisRef.attNameForClass]));},
				function(key,value){ 
					return thisRef.createAndCopy(value);});
	};
	JSONModelParser.prototype.reviver = function(key,value) {
		for(var i=0;i<this.matchers.length;i++) {
			if(this.matchers[i].m(key,value)) {
				value = this.matchers[i].o(key,value);
				if(this.firstMatchOnly)
					return value;					
			};
		}
		return value;
	};
	JSONModelParser.prototype.parse = function(objectOrJSONString) {
		var thisParser = this;
		return JSON.parse(objectOrJSONString,function(key,value){return thisParser.reviver(key,value);});
	};
	JSONModelParser.prototype.translate = function(object) {
		return this.parse(JSON.stringify(object));
	};
	/** 
	 * An object which has the specified getter will have the override applied (setter is assumed)
	 * @param enumClass
	 * @param owningClass
	 * @param getterName
	 * @param setterName
	 */
	JSONModelParser.prototype.addEnumField = function(enumClass, owningClass, getterName, setterName) {
		var thisRef = this;
		this.addOverride(
				function(key,value){ 
					return thisRef.hasSameMethod(getterName, value, owningClass.prototype);},
				function(key,value){ 
					var enumString = value[getterName].call(value);
					value[setterName].call(value,enumClass.valueOf(enumString));
					return value;
				});
	};
	
	JSONModelParser.prototype.trimClassName = function(classs) {
		var a = classs.split('.');
		if(a.length>0)
			return a[a.length-1];
		return '';
	};
	JSONModelParser.prototype.createAndCopy = function(from) {
		var className = from[this.attNameForClass];
		if(!className)
			className = from[this.attNameForType];
		if(className)
			return this.createAndCopyFromClass(className,from);
		return from;
	};
	JSONModelParser.prototype.createAndCopyFromClass = function(className, from) {
		className = this.trimClassName(className);
		var to = new window[className]();
		this.copyProperties(from,to);
		return to;
	};
	JSONModelParser.prototype.copyProperties = function(from,to) {
		for (var prop in from) {
		    if (from.hasOwnProperty(prop)) {
		    	to[prop] = from[prop];
		    }
		}
	};
	
	JSONModelParser.prototype.isClassOrType = function(value,expected) {
		return value!=null&&(this.trimClassName(value[this.attNameForClass]) == expected) || (value[this.attNameForType] == expected);
	};
	JSONModelParser.prototype.hasSameMethod = function(methodName, object, prototype) {
		return (object!=null&&object[methodName]!=null&&object[methodName]==prototype[methodName]);
	};

})();

(function() {	
	Number.prototype.compareTo = function(otherNum) { 
		return this != otherNum ? ((this > otherNum) ? 1 : -1) : 0;
	};
	
	/**
	 * BigDecimal implementation for javascript to support direct conversion of Java to javascript using STJS
	 */
	
	function BigDecimal(value) {
		if (typeof value == 'string' || value instanceof String)
			value = parseFloat(value);
		this.value = value;
	}
	window.BigDecimal = BigDecimal;
	
	BigDecimal.ZERO = new BigDecimal(0);
	BigDecimal.ONE = new BigDecimal(1);
	BigDecimal.TEN = new BigDecimal(10);
	
	BigDecimal.valueOf = function(val) {
		return new BigDecimal(val);
	};
	
	BigDecimal.prototype.compareTo = function(val) {
		return this.value != val.getValue() ? ((this.value > val.getValue()) ? 1 : -1) : 0;
	};
	
	BigDecimal.prototype.getTens = function(b) {
	    var index = String(this.value).indexOf('.');
	    if(index==-1)
	        length = 1;
	    var length = String(this.value).length - String(this.value).indexOf('.') - 1;
	    if(length>3)
	        length = 0;
	    return Math.pow(10,length);
	};
	
	BigDecimal.prototype.multiply = function(b) {
		var atens = this.getTens(this.value);
		var btens = this.getTens(b.getValue());
		var result = (this.value * atens) * (b.getValue() * btens) / (atens * btens); 
		return new BigDecimal(result);
	};
	
	BigDecimal.prototype.divide = function(b) {
		var atens = this.getTens(this.value);
		var btens = this.getTens(b.getValue());
		var result = (this.value * atens) / (b.getValue() * btens); 
		return new BigDecimal(result);
	};
	
	BigDecimal.prototype.add = function(b) {
		var tens = Math.max(this.getTens(this.value),this.getTens(b.getValue()));
		var result = (this.value*tens + b.getValue()*tens) / tens;
		return new BigDecimal(result);
	};
	
	BigDecimal.prototype.subtract = function(b) {
		var tens = Math.max(this.getTens(this.value),this.getTens(b));
	    var result = ((this.value*tens) - (b.getValue()*tens))/tens;  
	    return new BigDecimal(result);
	};
	
	BigDecimal.prototype.getValue = function() {
		return this.value;
	};
	
	BigDecimal.prototype.toPlainString = function() {
		return new String(this.value);
	};
	
})();

/*
 *  Copyright 2011 Alexandru Craciun, Eyal Kaspi
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
/**** Functionality in Java, but not in JS ********
 * methods added to JS prototypes
 */

var NOT_IMPLEMENTED = function(){
	throw "This method is not implemented in Javascript.";
}

JavalikeEquals = function(value){
	if (value == null)
		return false;
	if (value.valueOf)
		return this.valueOf() === value.valueOf();
	return this === value;
}

/* String */
if (!String.prototype.equals) {
	String.prototype.equals=JavalikeEquals;
}
if (!String.prototype.getBytes) {
	String.prototype.getBytes=NOT_IMPLEMENTED;
}
if (!String.prototype.getChars) {
	String.prototype.getChars=NOT_IMPLEMENTED;
}
if (!String.prototype.contentEquals){
	String.prototype.contentEquals=NOT_IMPLEMENTED;
}
if (!String.prototype.startsWith) {
	String.prototype.startsWith=function(start, from){
		var f = from != null ? from : 0;
		return this.substring(f, f + start.length) == start;
	}
}
if (!String.prototype.endsWith) {
	String.prototype.endsWith=function(end){
		if (end == null)
			return false;
		if (this.length < end.length)
			return false;
		return this.substring(this.length - end.length, this.length) == end;
	}
}
if (!String.prototype.trim) {
	var trimLeft = /^\s+/;
	var trimRight = /\s+$/;
	String.prototype.trim = function(  ) {
		return this.replace( trimLeft, "" ).replace( trimRight, "" );
	}
}
if (!String.prototype.matches){
	String.prototype.matches=function(regexp){
		return this.match("^" + regexp + "$") != null;
	}
}
if (!String.prototype.compareTo){
	String.prototype.compareTo=function(other){
		if (other == null)
			return 1;
		if (this < other)
			return -1;
		if (this == other)
			return 0;
		return 1;
	}
}

if (!String.prototype.compareToIgnoreCase){
	String.prototype.compareToIgnoreCase=function(other){
		if (other == null)
			return 1;
		return this.toLowerCase().compareTo(other.toLowerCase());
	}
}

if (!String.prototype.equalsIgnoreCase){
	String.prototype.equalsIgnoreCase=function(other){
		if (other == null)
			return false;
		return this.toLowerCase() === other.toLowerCase();
	}
}

if (!String.prototype.codePointAt){
	String.prototype.codePointAt=String.prototype.charCodeAt;
}

if (!String.prototype.codePointBefore){
	String.prototype.codePointBefore=NOT_IMPLEMENTED;
}
if (!String.prototype.codePointCount){
	String.prototype.codePointCount=NOT_IMPLEMENTED;
}

if (!String.prototype.replaceAll){
	String.prototype.replaceAll=function(regexp, replace){
		return this.replace(new RegExp(regexp, "g"), replace);
	}
}

if (!String.prototype.replaceFirst){
	String.prototype.replaceFirst=function(regexp, replace){
		return this.replace(new RegExp(regexp), replace);
	}
}

if (!String.prototype.regionMatches){
	String.prototype.regionMatches=function(ignoreCase, toffset, other, ooffset, len){
		if (arguments.length == 4){
			len=arguments[3];
			ooffset=arguments[2];
			other=arguments[1];
			toffset=arguments[0];
			ignoreCase=false;
		}
		if (toffset < 0 || ooffset < 0 || other == null || toffset + len > this.length || ooffset + len > other.length)
			return false;
		var s1 = this.substring(toffset, toffset + len);
		var s2 = other.substring(ooffset, ooffset + len);
		return ignoreCase ? s1.equalsIgnoreCase(s2) : s1 === s2;
	}
}



//force valueof to match the Java's behavior
String.valueOf=function(value){
	return new String(value);
}

/* Number */
var Byte=Number;
var Double=Number;
var Float=Number;
var Integer=Number;
var Long=Number;
var Short=Number;

/* type conversion - approximative as Javascript only has integers and doubles */
if (!Number.prototype.intValue) {
	Number.prototype.intValue=function(){
		return parseInt(this);
	}
}
if (!Number.prototype.shortValue) {
	Number.prototype.shortValue=function(){
		return parseInt(this);
	}
}
if (!Number.prototype.longValue) {
	Number.prototype.longValue=function(){
		return parseInt(this);
	}
}
if (!Number.prototype.byteValue) {
	Number.prototype.byteValue=function(){
		return parseInt(this);
	}
}

if (!Number.prototype.floatValue) {
	Number.prototype.floatValue=function(){
		return parseFloat(this);
	}
}

if (!Number.prototype.doubleValue) {
	Number.prototype.doubleValue=function(){
		return parseFloat(this);
	}
}

if (!Number.parseInt) {
	Number.parseInt = parseInt;
}
if (!Number.parseShort) {
	Number.parseShort = parseInt;
}
if (!Number.parseLong) {
	Number.parseLong = parseInt;
}
if (!Number.parseByte) {
	Number.parseByte = parseInt;
}

if (!Number.parseDouble) {
	Number.parseDouble = parseFloat;
}

if (!Number.parseFloat) {
	Number.parseFloat = parseFloat;
}

if (!Number.isNaN) {
	Number.isNaN = isNaN;
}

if (!Number.prototype.isNaN) {
	Number.prototype.isNaN = isNaN;
}
if (!Number.prototype.equals) {
	Number.prototype.equals=JavalikeEquals;
}

//force valueof to match approximately the Java's behavior (for Integer.valueOf it returns in fact a double)
Number.valueOf=function(value){
	return new Number(value).valueOf();
}

/* Boolean */
if (!Boolean.prototype.equals) {
	Boolean.prototype.equals=JavalikeEquals;
}

//force valueof to match the Java's behavior
Boolean.valueOf=function(value){
	return new Boolean(value).valueOf();
}



/************* STJS helper functions ***************/
var stjs={};

stjs.global=this;
stjs.skipCopy = {"prototype":true, "constructor": true, "$typeDescription":true, "$inherit" : true};

stjs.ns=function(path){
	var p = path.split(".");
	var obj = stjs.global;
	for(var i = 0; i < p.length; ++i){
		var part = p[i];
		obj = obj[part] = obj[part] || {};
	}
	return obj;
};

stjs.copyProps=function(from, to){
	for(key in from){
		if (!stjs.skipCopy[key])
			to[key]	= from[key];
	}
	return to;
};

stjs.copyInexistentProps=function(from, to){
	for(key in from){
		if (!stjs.skipCopy[key] && !to[key])
			to[key]	= from[key];
	}
	return to;
};

stjs.extend=function(_constructor, _super, _implements, _initializer, _typeDescription){
	if(typeof(_typeDescription) !== "object"){
		// stjs 1.3+ always passes an non-null object to _typeDescription => The code calling stjs.extend
		// was generated with version 1.2 or earlier, so let's call the 1.2 version of stjs.extend
		return stjs.extend12.apply(this, arguments);
	}

	_constructor.$inherit=[];
	var key, a;
	if(_super != null){
		// I is used as a no-op constructor that has the same prototype as _super
		// we do this because we cannot predict the result of calling new _super()
		// without parameters (it might throw an exception).
		// Basically, the following 3 lines are a safe equivalent for
		// _constructor.prototype = new _super();
		var I = function(){};
		I.prototype	= _super.prototype;
		_constructor.prototype	= new I();

		// copy static properties for super
		// assign every method from proto instance
		stjs.copyProps(_super, _constructor);
		stjs.copyProps(_super.$typeDescription, _typeDescription);

		//add the super class to inherit array
		_constructor.$inherit.push(_super);
	}

	// copy static properties and default methods from interfaces
	for(a = 0; a < _implements.length; ++a){
		stjs.copyProps(_implements[a], _constructor);
		stjs.copyInexistentProps(_implements[a].prototype, _constructor.prototype);
		_constructor.$inherit.push(_implements[a]);
	}

	// remember the correct constructor
	_constructor.prototype.constructor	= _constructor;

	// run the initializer to assign all static and instance variables/functions
	if(_initializer != null){
		_initializer(_constructor, _constructor.prototype);
	}

	_constructor.$typeDescription = _typeDescription;

	// add the default equals method if it is not present yet, and we don't have a superclass
	if(_super == null && !_constructor.prototype.equals){
		_constructor.prototype.equals = JavalikeEquals;
	}

	// build package and assign
	return	_constructor;
};

/**
 * 1.2 and earlier version of stjs.extend. Included for backwards compatibility
 */
stjs.extend12=function( _constructor,  _super, _implements){
	var key, a;
	var I = function(){};
	I.prototype	= _super.prototype;
	_constructor.prototype	= new I();

	//copy static properties for super and interfaces
	// assign every method from proto instance
	for(a = 1; a < arguments.length; ++a){
		stjs.copyProps(arguments[a], _constructor);
	}
	// remember the correct constructor
	_constructor.prototype.constructor	= _constructor;

	// add the default equals method if we don't have a superclass. Code generated with version 1.2 will
	// override this method is equals() is present in the original java code.
	// this was not part of the original 1.2 version of extends, however forward compatibility
	// with 1.3 requires it
	if(_super == null){
		_constructor.prototype.equals = JavalikeEquals;
	}

	// build package and assign
	return	_constructor;
};

/**
 * checks if the child is an instanceof parent. it checks recursively if "parent" is the child itself or it's found somewhere in the $inherit array
 */
stjs.isInstanceOf=function(child, parent){
	if (child === parent)
		return true;
	if (!child.$inherit)
		return false;
	for(var i in child.$inherit){
		if (stjs.isInstanceOf(child.$inherit[i], parent)) {
			return true;
		}
	}
	return false;
}
stjs.enumEntry=function(idx, name){
	this._name = name;
	this._ordinal = idx;
};

stjs.enumEntry.prototype.name=function(){
	return this._name;
};
stjs.enumEntry.prototype.ordinal=function(){
	return this._ordinal;
};
stjs.enumEntry.prototype.toString=function(){
	return this._name;
};
stjs.enumEntry.prototype.equals=JavalikeEquals;

stjs.enumeration=function(){
	var i;
	var e = {};
	e._values = [];
	for(i = 0; i < arguments.length; ++i){
		e[arguments[i]] = new stjs.enumEntry(i, arguments[i]);
		e._values[i] = e[arguments[i]];
	}
	e.values = function(){return this._values;};
	e.valueOf=function(label){
		return this[label];
	}
	return e;
};

/**
 * if true the execution of generated main methods is disabled.
 * this is useful when executing unit tests, to no have the main methods executing before the tests
 */
stjs.mainCallDisabled = false;

stjs.exception=function(err){
	return err;
}

stjs.isEnum=function(obj){
	return obj != null && obj.constructor == stjs.enumEntry;
}

stjs.trunc=function(n) {
	if (n == null)
		return null;
	return n | 0;
}

stjs.converters = {
	Date : function(s, type) {
		var a = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)$/
				.exec(s);
		if (a) {
			return new Date(+a[1], +a[2] - 1, +a[3], +a[4], +a[5], +a[6]);
		}
		return null;
	},

	Enum : function(s, type){
		return eval(type.arguments[0])[s];
	}
};

/** *********** global ************** */
function exception(err){
	return err;
}

function isEnum(obj){
	return obj != null && obj.constructor == stjs.enumEntry;
}

/******* parsing *************/

/**
 * parse a json string using the type definition to build a typed object hierarchy
 */
stjs.parseJSON = (function () {
	  var number
	      = '(?:-?\\b(?:0|[1-9][0-9]*)(?:\\.[0-9]+)?(?:[eE][+-]?[0-9]+)?\\b)';
	  var oneChar = '(?:[^\\0-\\x08\\x0a-\\x1f\"\\\\]'
	      + '|\\\\(?:[\"/\\\\bfnrt]|u[0-9A-Fa-f]{4}))';
	  var string = '(?:\"' + oneChar + '*\")';

	  // Will match a value in a well-formed JSON file.
	  // If the input is not well-formed, may match strangely, but not in an unsafe
	  // way.
	  // Since this only matches value tokens, it does not match whitespace, colons,
	  // or commas.
	  var jsonToken = new RegExp(
	      '(?:false|true|null|[\\{\\}\\[\\]]'
	      + '|' + number
	      + '|' + string
	      + ')', 'g');

	  // Matches escape sequences in a string literal
	  var escapeSequence = new RegExp('\\\\(?:([^u])|u(.{4}))', 'g');

	  // Decodes escape sequences in object literals
	  var escapes = {
	    '"': '"',
	    '/': '/',
	    '\\': '\\',
	    'b': '\b',
	    'f': '\f',
	    'n': '\n',
	    'r': '\r',
	    't': '\t'
	  };
	  function unescapeOne(_, ch, hex) {
	    return ch ? escapes[ch] : String.fromCharCode(parseInt(hex, 16));
	  }

	  var constructors = {};

	  function constr(name, param){
		  var c = constructors[name];
		  if (!c)
			  constructors[name] = c = eval(name);
		  return new c(param);
	  }

	  function convert(type, json){
		  if (!type)
			  return json;
		  var cv = stjs.converters[type.name || type];
		  if (cv)
			  return cv(json, type);
		  //hopefully the type has a string constructor
		 return constr(type, json);
	  }

	  function builder(type){
		  if (!type)
			  return {};
			if (typeof type == "function")
				return new type();
			if (type.name) {
				if (type.name == "Map")
					return {};
				if (type.name == "Array")
					return [];
				return constr(type.name);
			}
			return constr(type);
	  }

	  // A non-falsy value that coerces to the empty string when used as a key.
	  var EMPTY_STRING = new String('');
	  var SLASH = '\\';

	  // Constructor to use based on an open token.
	  var firstTokenCtors = { '{': Object, '[': Array };

	  var hop = Object.hasOwnProperty;

	  function nextMatch(str){
		  var m = jsonToken.exec(str);
		  return m != null ? m[0] : null;
	  }
	  return function (json, type) {
	    // Split into tokens
	    // Construct the object to return
	    var result;
	    var tok = nextMatch(json);
	    var topLevelPrimitive = false;
	    if ('{' === tok) {
	      result = builder(type, null);
	    } else if ('[' === tok) {
	      result = [];
	    } else {
	      // The RFC only allows arrays or objects at the top level, but the JSON.parse
	      // defined by the EcmaScript 5 draft does allow strings, booleans, numbers, and null
	      // at the top level.
	      result = [];
	      topLevelPrimitive = true;
	    }

	    // If undefined, the key in an object key/value record to use for the next
	    // value parsed.
	    var key;
	    // Loop over remaining tokens maintaining a stack of uncompleted objects and
	    // arrays.
	    var stack = [result];
	    var stack2 = [type];
	    for (tok = nextMatch(json); tok != null; tok = nextMatch(json)) {

	      var cont;
	      switch (tok.charCodeAt(0)) {
	        default:  // sign or digit
	          cont = stack[0];
	          cont[key || cont.length] = +(tok);
	          key = void 0;
	          break;
	        case 0x22:  // '"'
	          tok = tok.substring(1, tok.length - 1);
	          if (tok.indexOf(SLASH) !== -1) {
	            tok = tok.replace(escapeSequence, unescapeOne);
	          }
	          cont = stack[0];
	          if (!key) {
	            if (cont instanceof Array) {
	              key = cont.length;
	            } else {
	              key = tok || EMPTY_STRING;  // Use as key for next value seen.
	              stack2[0] = cont.constructor.$typeDescription ? cont.constructor.$typeDescription[key] : stack2[1].arguments[1];
	              break;
	            }
	          }
	          cont[key] = convert(stack2[0],tok);
	          key = void 0;
	          break;
	        case 0x5b:  // '['
	          cont = stack[0];
	          stack.unshift(cont[key || cont.length] = []);
	          stack2.unshift(stack2[0].arguments[0]);
	          //put the element type desc
	          key = void 0;
	          break;
	        case 0x5d:  // ']'
	          stack.shift();
	          stack2.shift();
	          break;
	        case 0x66:  // 'f'
	          cont = stack[0];
	          cont[key || cont.length] = false;
	          key = void 0;
	          break;
	        case 0x6e:  // 'n'
	          cont = stack[0];
	          cont[key || cont.length] = null;
	          key = void 0;
	          break;
	        case 0x74:  // 't'
	          cont = stack[0];
	          cont[key || cont.length] = true;
	          key = void 0;
	          break;
	        case 0x7b:  // '{'
	          cont = stack[0];
	          stack.unshift(cont[key || cont.length] = builder(stack2[0]));
	          stack2.unshift(null);
	          key = void 0;
	          break;
	        case 0x7d:  // '}'
	          stack.shift();
	          stack2.shift();
	          break;
	      }
	    }
	    // Fail if we've got an uncompleted object.
	    if (topLevelPrimitive) {
	      if (stack.length !== 1) { throw new Error(); }
	      result = result[0];
	    } else {
	      if (stack.length) { throw new Error(); }
	    }

	    return result;
	  };
})();

/************* STJS asserts ***************/
var stjsAssertHandler = function(position, code, msg) {
	throw msg + " at " + position;
}
function setAssertHandler(a) {
	stjsAssertHandler = a;
}

function assertArgEquals(position, code, expectedValue, testValue) {
	if (expepectedValue != testValue && stjsAssertHandler)
		stjsAssertHandler(position, code, "Wrong argument. Expected: " + expectedValue + ", got:" + testValue);
}

function assertArgNotNull(position, code, testValue) {
	if (testValue == null && stjsAssertHandler)
		stjsAssertHandler(position, code, "Wrong argument. Null value");
}

function assertArgTrue(position, code, condition) {
	if (!condition && stjsAssertHandler)
		stjsAssertHandler(position, code, "Wrong argument. Condition is false");
}

function assertStateEquals(position, code, expectedValue, testValue) {
	if (expepectedValue != testValue && stjsAssertHandler)
		stjsAssertHandler(position, code, "Wrong state. Expected: " + expectedValue + ", got:" + testValue);
}

function assertStateNotNull(position, code, testValue) {
	if (testValue == null && stjsAssertHandler)
		stjsAssertHandler("Wrong state. Null value");
}

function assertStateTrue(position, code, condition) {
	if (!condition && stjsAssertHandler)
		stjsAssertHandler(position, code, "Wrong state. Condition is false");
}
/** exception **/
var Throwable = function(message, cause){
	if (typeof message === "string"){
		this.detailMessage  = message;
		this.message = message;
		this.cause = cause;
	} else {
		this.cause = message;
	}
};
stjs.extend(Throwable, Error, [], function(constructor, prototype){
	prototype.detailMessage = null;
	prototype.cause = null;
	prototype.getMessage = function() {
        return this.detailMessage;
    };

	prototype.getLocalizedMessage = function() {
        return this.getMessage();
    };

	prototype.getCause = function() {
        return (this.cause==this ? null : this.cause);
    };

	prototype.toString = function() {
	        var s = "Exception";//TODO should get the exception's type name here
	        var message = this.getLocalizedMessage();
	        return (message != null) ? (s + ": " + message) : s;
	 };

	 //TODO use stacktrace.js script
	 prototype.getStackTrace = function() {
		 return this.stack;
	 };

	 //TODO use stacktrace.js script
	 prototype.printStackTrace = function(){
		 console.error(this.getStackTrace());
	 };
}, {});

var Exception = function(message, cause){
	Throwable.call(this, message, cause);
};
stjs.extend(Exception, Throwable, [], function(constructor, prototype){
}, {});

var RuntimeException = function(message, cause){
	Exception.call(this, message, cause);
};
stjs.extend(RuntimeException, Exception, [], function(constructor, prototype){
}, {});var BooleanOperator = stjs.enumeration("AND", "OR", "NOT", "NON_EMPTY");

var DisplayHint = function() {};
stjs.extend(DisplayHint, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.value = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {});

var ValueType = stjs.enumeration("STRING", "BOOLEAN", "DECIMAL", "INTEGER", "LONG");

var InsertPosition = stjs.enumeration("AFTERPAGE", "BEFOREPAGE");

var ProductContext = function() {};
stjs.extend(ProductContext, null, [], null, {});

var ValidationResultProvider = function() {};
stjs.extend(ValidationResultProvider, null, [], null, {});

var TextCharacterization = stjs.enumeration("HEADER", "FOOTER", "BULLET", "HIGHLIGHT", "BOLD");

var ValidationType = stjs.enumeration("BOUNDS", "RESTRICTION", "SELECTABLE", "CONTENT", "REQUIRED", "OTHER");

var ProductConfigProcessorException = function(message, cause) {
    Exception.call(this, message, cause);
};
stjs.extend(ProductConfigProcessorException, Exception, [], null, {});

var WeightUnit = stjs.enumeration("LB");

var CatalogReference = function() {};
stjs.extend(CatalogReference, null, [], function(constructor, prototype) {
    prototype.catalogProductId = null;
    prototype.version = null;
    prototype.getCatalogProductId = function() {
        return this.catalogProductId;
    };
    prototype.setCatalogProductId = function(catalogProductId) {
        this.catalogProductId = catalogProductId;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
}, {});

var PageExceptionType = stjs.enumeration("TAB", "INSERT", "PRINT_EXCEPTION");

var ElementType = stjs.enumeration("PRODUCT", "PAGEEXCEPTION");

var RuleConfigurationException = function(message, cause) {
    Exception.call(this, message, cause);
};
stjs.extend(RuleConfigurationException, Exception, [], null, {});

var ValidationResultCode = stjs.enumeration("PROPERTY_VALUE_REQUIRED", "PROPERTY_VALUE_MIN_REQUIRED", "PROPERTY_VALUE_MAX_EXCEEDED", "PROPERTY_VALUE_NOT_ALLOWED", "PROPERTY_VALUE_FORMAT_INVALID", "FEATURE_NOT_SELECTABLE", "CHOICE_NOT_SELECTABLE", "PROPERTY_NOT_ALLOWED", "FEATURE_REQUIRED", "CHOICE_REQUIRED", "PROPERTY_REQUIRED", "EXCEPTION_PAGE_RANGE_REQUIRED", "EXCEPTION_FEATURE_REQUIRED", "EXCEPTION_START_PAGE_INVALID", "EXCEPTION_END_PAGE_INVALID", "EXCEPTION_NOT_SELECTABLE", "PAGE_EXCEPTION_PAGE_RANGE_INVALID", "CONTENT_PAGE_COUNT_MAX_EXCEEDED", "CONTENT_PAGE_COUNT_MIN_REQUIRED", "CONTENT_FILE_SIZE_MAX_EXCEEDED", "CONTENT_MORE_FILES_ALLOWED", "CONTENT_PAGE_SIZE_INVALID", "CONTENT_PAGE_SIZE_MIXED_NOT_ALLOWED", "CONTENT_ORIENTATION_MIXED_NOT_ALLOWED", "CONTENT_PRINT_READY_REQUIRED", "CONTENT_FILE_COUNT_MAX_EXCEEDED", "CONTENT_REQUIREMENT_PURPOSE_REQUIRED", "CONTENT_REFERENCE_INVALID", "CONTENT_NOT_ALLOWED", "PRODUCT_ID_REQUIRED", "FEATURE_ID_REQUIRED", "CHOICE_ID_REQUIRED", "PROPERTY_ID_REQUIRED", "PRODUCT_ID_INVALID", "PRODUCT_ID_VERSION_INVALID", "PRODUCT_PROCESSING_ERROR", "PRODUCT_INTERNAL_ERROR", "FEATURE_ID_INVALID", "CHOICE_ID_INVALID", "PROPERTY_ID_INVALID", "CONTENT_REQUIREMENT_ID_INVALID", "CONTENT_REQUIREMENT_ID_REQUIRED", "CONTENT_REQUIREMENT_REQUIRED", "PRODUCT_QUANTITY_INVALID", "CONTENT_REFERENCE_OR_PAGE_GROUP_REQUIRED", "CONTENT_PAGE_GROUP_INVALID", "DUPLICATE_PRODUCT_ELEMENT", "PRODUCTION_TIME_REQUIRED", "PRODUCTION_WEIGHT_REQUIRED", "PRODUCTION_TIME_INVALID", "WEIGHT_INVALID");

var ValidationSeverity = stjs.enumeration("ERROR", "WARNING", "INFO");

var TimeUnit = stjs.enumeration("DAY", "HOUR");

var PageRange = function() {};
stjs.extend(PageRange, null, [], function(constructor, prototype) {
    prototype.start = 0;
    prototype.end = 0;
    prototype.getStart = function() {
        return this.start;
    };
    prototype.setStart = function(start) {
        this.start = start;
    };
    prototype.getEnd = function() {
        return this.end;
    };
    prototype.setEnd = function(end) {
        this.end = end;
    };
    prototype.clone = function() {
        var r = new PageRange();
        r.setStart(this.start);
        r.setEnd(this.end);
        return r;
    };
}, {});

var ProductDisplayProcessorException = function(message, cause) {
    Exception.call(this, message, cause);
};
stjs.extend(ProductDisplayProcessorException, Exception, [], function(constructor, prototype) {
    /**
     *  
     */
    constructor.serialVersionUID = 1;
}, {});

var CalcOperator = stjs.enumeration("ADD", "SUB", "MULT", "DIV", "DIV_ROUND_UP", "ROUND_UP_TO_MULTIPLE", "MOD");

var ValueProvider = function() {};
stjs.extend(ValueProvider, null, [], null, {});

var RuleDefinition = function() {};
stjs.extend(RuleDefinition, null, [], null, {});

var Condition = function() {};
stjs.extend(Condition, null, [], null, {});

var AbstractRuleElement = function() {};
stjs.extend(AbstractRuleElement, null, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
}, {});

/**
 *  Represents a reference with a name and value.
 */
var Reference = function() {};
stjs.extend(Reference, null, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.value = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {});

var PresetChoice = function() {};
stjs.extend(PresetChoice, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.select = false;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.isSelect = function() {
        return this.select;
    };
    prototype.setSelect = function(select) {
        this.select = select;
    };
}, {});

var ExternalRequirements = function() {};
stjs.extend(ExternalRequirements, null, [], function(constructor, prototype) {
    prototype.weightRequired = false;
    prototype.productionTimeRequired = false;
    prototype.isWeightRequired = function() {
        return this.weightRequired;
    };
    prototype.setWeightRequired = function(weightRequired) {
        this.weightRequired = weightRequired;
    };
    prototype.isProductionTimeRequired = function() {
        return this.productionTimeRequired;
    };
    prototype.setProductionTimeRequired = function(productionTimeRequired) {
        this.productionTimeRequired = productionTimeRequired;
    };
}, {});

var ContentDimensions = function() {};
stjs.extend(ContentDimensions, null, [], function(constructor, prototype) {
    prototype.width = 0.0;
    prototype.height = 0.0;
    prototype.getWidth = function() {
        return this.width;
    };
    prototype.setWidth = function(width) {
        this.width = width;
    };
    prototype.getHeight = function() {
        return this.height;
    };
    prototype.setHeight = function(height) {
        this.height = height;
    };
}, {});

var ProductElement = function() {};
stjs.extend(ProductElement, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.hashCode = function() {
        return (this.id == null ? 0 : this.id.hashCode());
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
}, {});

var CompatibilitySubGroup = function() {};
stjs.extend(CompatibilitySubGroup, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
}, {});

var FeatureInstanceContainer = function() {};
stjs.extend(FeatureInstanceContainer, null, [], null, {});

var ContentOrientation = stjs.enumeration("PORTRAIT", "LANDSCAPE");

/**
 *  Represents the file type, in majority of the cases the file type will be PDF
 *  
 *  @author 5010701
 *  @author Naga Vankayalapati
 */
var ContentType = stjs.enumeration("PDF");

var DesignVendorCode = stjs.enumeration("CUSTOMERS_CANVAS", "CANVA");

var PropertyAllowedValue = function() {};
stjs.extend(PropertyAllowedValue, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
}, {});

var PropertyInputDetailsValue = function() {};
stjs.extend(PropertyInputDetailsValue, null, [], function(constructor, prototype) {
    prototype.sequence = 0;
    prototype.name = null;
    prototype.value = null;
    prototype.getSequence = function() {
        return this.sequence;
    };
    prototype.setSequence = function(sequence) {
        this.sequence = sequence;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {});

var DataType = stjs.enumeration("NUMERIC", "DECIMAL", "ALPHA", "ALPHA_NUMERIC");

var BleedRange = function() {};
stjs.extend(BleedRange, null, [], function(constructor, prototype) {
    prototype.start = 0.0;
    prototype.end = 0.0;
    prototype.getStart = function() {
        return this.start;
    };
    prototype.setStart = function(start) {
        this.start = start;
    };
    prototype.getEnd = function() {
        return this.end;
    };
    prototype.setEnd = function(end) {
        this.end = end;
    };
}, {});

var ComparisonOperator = stjs.enumeration("GREATER", "LESSER", "EQUALS", "GREATER_OR_EQUAL", "LESSER_OR_EQUAL", "NOT_EQUALS");

/**
 *  Represents the state of the file
 *  
 *  @author 5010701
 *  @author Naga Vankayalapati
 */
var ContentState = stjs.enumeration("IMPOSED", "PRINT_READY");

/**
 *  @author Jai Kumar
 */
var VendorEstimatedDeliveryRange = function() {};
stjs.extend(VendorEstimatedDeliveryRange, null, [], function(constructor, prototype) {
    prototype.startDate = null;
    prototype.endDate = null;
    /**
     *  @return the startDate
     */
    prototype.getStartDate = function() {
        return this.startDate;
    };
    /**
     *  @param startDate the startDate to set
     */
    prototype.setStartDate = function(startDate) {
        this.startDate = startDate;
    };
    /**
     *  @return the endDate
     */
    prototype.getEndDate = function() {
        return this.endDate;
    };
    /**
     *  @param endDate the endDate to set
     */
    prototype.setEndDate = function(endDate) {
        this.endDate = endDate;
    };
}, {});

var ContentPurpose = stjs.enumeration("SPINE", "MAIN_CONTENT");

var DimensionUnit = stjs.enumeration("INCH");

var ProductReference = function() {};
stjs.extend(ProductReference, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.version = 0;
    prototype.unitQty = 0;
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getUnitQty = function() {
        return this.unitQty;
    };
    prototype.setUnitQty = function(unitQty) {
        this.unitQty = unitQty;
    };
}, {});

var DisplayValueType = stjs.enumeration("TIME", "WEIGHT");

var DisplayText = function() {};
stjs.extend(DisplayText, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.text = null;
    prototype.sequence = 0;
    prototype.characterization = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getText = function() {
        return this.text;
    };
    prototype.setText = function(text) {
        this.text = text;
    };
    prototype.getSequence = function() {
        return this.sequence;
    };
    prototype.setSequence = function(sequence) {
        this.sequence = sequence;
    };
    prototype.getCharacterization = function() {
        return this.characterization;
    };
    prototype.setCharacterization = function(characterization) {
        this.characterization = characterization;
    };
}, {characterization: {name: "Set", arguments: [null]}});

var DisplayEntry = function() {};
stjs.extend(DisplayEntry, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.version = null;
    prototype.entries = null;
    prototype.hashCode = function() {
        return this.id.hashCode();
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getEntries = function() {
        return this.entries;
    };
    prototype.setEntries = function(entries) {
        this.entries = entries;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
}, {entries: {name: "Set", arguments: ["DisplayEntry"]}});

var DisplayGroup = function() {
    this.displayGroups = new ArrayList();
};
stjs.extend(DisplayGroup, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.value = null;
    prototype.displayGroups = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
    prototype.getDisplayGroups = function() {
        return this.displayGroups;
    };
    prototype.setDisplayGroups = function(displayGroups) {
        this.displayGroups = displayGroups;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
}, {displayGroups: {name: "List", arguments: ["DisplayGroup"]}});

var FeatureReference = function() {
    this.choiceIds = new ArraySet();
};
stjs.extend(FeatureReference, null, [], function(constructor, prototype) {
    prototype.featureId = null;
    prototype.defaultChoiceId = null;
    prototype.choiceIds = null;
    prototype.getFeatureId = function() {
        return this.featureId;
    };
    prototype.setFeatureId = function(featureId) {
        this.featureId = featureId;
    };
    prototype.getDefaultChoiceId = function() {
        return this.defaultChoiceId;
    };
    prototype.setDefaultChoiceId = function(defaultChoiceId) {
        this.defaultChoiceId = defaultChoiceId;
    };
    prototype.getChoiceIds = function() {
        return this.choiceIds;
    };
    prototype.setChoiceIds = function(choiceIds) {
        this.choiceIds = choiceIds;
    };
    prototype.clone = function() {
        var fr = new FeatureReference();
        fr.setFeatureId(this.getFeatureId());
        fr.setDefaultChoiceId(this.getDefaultChoiceId());
        var cIds = new ArraySet();
        var it = this.choiceIds.iterator();
         while (it.hasNext()){
            cIds.add(it.next());
        }
        fr.setChoiceIds(cIds);
        return fr;
    };
}, {choiceIds: {name: "Set", arguments: [null]}});

var RuleType = function() {
    this.multAllowed = false;
};
stjs.extend(RuleType, null, [], function(constructor, prototype) {
    constructor.map = new ArrayMap();
    prototype.id = null;
    prototype.desc = null;
    prototype.multAllowed = false;
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.isMultAllowed = function() {
        return this.multAllowed;
    };
    prototype.setMultAllowed = function(multAllowed) {
        this.multAllowed = multAllowed;
    };
    constructor.getAllTypes = function() {
        return RuleType.map.values();
    };
    constructor.createRuleType = function(id, desc, multAllowed) {
        var type = new RuleType();
        type.setId(id);
        type.setDesc(desc);
        type.setMultAllowed(multAllowed);
        RuleType.map.put(id, type);
        return type;
    };
    constructor.valueOf = function(id) {
        return RuleType.map.get(id);
    };
    constructor.DEFAULT_SELECTION = RuleType.createRuleType("DEFAULT_SELECTION", "Default Selection", false);
    constructor.VALIDATION = RuleType.createRuleType("VALIDATION", "Validation", true);
    constructor.SKU_CODE = RuleType.createRuleType("SKU_CODE", "SKU Code Override", false);
    constructor.REQUIRED = RuleType.createRuleType("REQUIRED", "Required", false);
    constructor.AVAILABLE = RuleType.createRuleType("AVAILABLE", "Selectability", false);
    constructor.VALUE = RuleType.createRuleType("VALUE", "Value Override", false);
    constructor.ASSOCIATE_TEXT = RuleType.createRuleType("ASSOCIATE_TEXT", "Associate Text", false);
    constructor.PRICEABLE = RuleType.createRuleType("PRICEABLE", "Priceable Override", false);
    constructor.OVERRIDE_DEFAULT = RuleType.createRuleType("OVERRIDE_DEFAULT", "Default Override", false);
    constructor.OVERRIDE_DEFAULT_FLAG = RuleType.createRuleType("OVERRIDE_DEFAULT_FLAG", "Override Default Flag", false);
    constructor.DEFAULT_OVERRIDE = RuleType.createRuleType("DEFAULT_OVERRIDE", "Default Override", false);
    constructor.SKU_REFERENCE = RuleType.createRuleType("SKU_REFERENCE", "Sku Reference", false);
    constructor.SKU_QTY = RuleType.createRuleType("SKU_QTY", "SKU qty Override", false);
    constructor.SKU_UNIT_QTY = RuleType.createRuleType("SKU_UNIT_QTY", "SKU unit qty Override", false);
    constructor.SKU_DSC_LOOKUP_QTY = RuleType.createRuleType("SKU_DSC_LOOKUP_QTY", "SKU discount lookup qty Override", false);
    constructor.PROOF_FLAG = RuleType.createRuleType("PROOF_FLAG", "Proof required Override", false);
    constructor.PRODUCTION_TIME = RuleType.createRuleType("PRODUCTION_TIME", "Production Time Override", false);
    constructor.OUTSOURCE = RuleType.createRuleType("OUTSOURCE", "Outsource required", false);
    constructor.PRODUCT_QTY = RuleType.createRuleType("PRODUCT_QTY", "Quantity override", false);
    constructor.WEIGHT_QTY = RuleType.createRuleType("WEIGHT_QTY", "Weight override", false);
    constructor.DLT_EXPRESSION = RuleType.createRuleType("DLT_EXPRESSION", "DLT Expression", false);
    constructor.PROD_TIME_QTY = RuleType.createRuleType("PROD_TIME_QTY", "Production Time Quantity", false);
    constructor.PRODUCTION_QTY = RuleType.createRuleType("PRODUCTION_QTY", "Production Quantity", false);
    constructor.PRODUCTION_CAPABILITY = RuleType.createRuleType("PRODUCTION_CAPABILITY", "Production Quantity", false);
    constructor.PRIORITY_TIME = RuleType.createRuleType("PRIORITY_TIME", "Priority Time", false);
    constructor.NFC_TIME = RuleType.createRuleType("NFC_TIME", "NFC Time", false);
    constructor.STANDARD_TIME = RuleType.createRuleType("STANDARD_TIME", "Standard Time", false);
}, {map: {name: "Map", arguments: [null, "RuleType"]}, DEFAULT_SELECTION: "RuleType", VALIDATION: "RuleType", SKU_CODE: "RuleType", REQUIRED: "RuleType", AVAILABLE: "RuleType", VALUE: "RuleType", ASSOCIATE_TEXT: "RuleType", PRICEABLE: "RuleType", OVERRIDE_DEFAULT: "RuleType", OVERRIDE_DEFAULT_FLAG: "RuleType", DEFAULT_OVERRIDE: "RuleType", SKU_REFERENCE: "RuleType", SKU_QTY: "RuleType", SKU_UNIT_QTY: "RuleType", SKU_DSC_LOOKUP_QTY: "RuleType", PROOF_FLAG: "RuleType", PRODUCTION_TIME: "RuleType", OUTSOURCE: "RuleType", PRODUCT_QTY: "RuleType", WEIGHT_QTY: "RuleType", DLT_EXPRESSION: "RuleType", PROD_TIME_QTY: "RuleType", PRODUCTION_QTY: "RuleType", PRODUCTION_CAPABILITY: "RuleType", PRIORITY_TIME: "RuleType", NFC_TIME: "RuleType", STANDARD_TIME: "RuleType"});

/**
 *  Copyright (c) 2020 Fedex. All Rights Reserved.<br>
 * 
 *  Feature - Rate Service PI20.5: Implementation of cart.<br>
 *  User Story - B-359505 Create Request DTOs for Save Cart Request in Rating
 *  Service.<br>
 * 
 *  Description: This is a Skus class for RateAndSave Request .<br>
 * 
 *  @author Rinkal Singh Tomar[3905909]
 *  @since April 13, 2020
 *  @version 1.0
 */
var ExternalSku = function() {};
stjs.extend(ExternalSku, null, [], function(constructor, prototype) {
    prototype.skuDescription = null;
    prototype.skuRef = null;
    prototype.code = null;
    prototype.unitPrice = null;
    prototype.price = null;
    prototype.qty = 0;
    prototype.applyProductQty = false;
    prototype.getSkuDescription = function() {
        return this.skuDescription;
    };
    prototype.setSkuDescription = function(skuDescription) {
        this.skuDescription = skuDescription;
    };
    prototype.getSkuRef = function() {
        return this.skuRef;
    };
    prototype.setSkuRef = function(skuRef) {
        this.skuRef = skuRef;
    };
    prototype.getCode = function() {
        return this.code;
    };
    prototype.setCode = function(code) {
        this.code = code;
    };
    prototype.getUnitPrice = function() {
        return this.unitPrice;
    };
    prototype.setUnitPrice = function(unitPrice) {
        this.unitPrice = unitPrice;
    };
    prototype.getPrice = function() {
        return this.price;
    };
    prototype.setPrice = function(price) {
        this.price = price;
    };
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.isApplyProductQty = function() {
        return this.applyProductQty;
    };
    prototype.setApplyProductQty = function(applyProductQty) {
        this.applyProductQty = applyProductQty;
    };
}, {unitPrice: "BigDecimal", price: "BigDecimal"});

var ExternalProductionWeight = function() {
    this.units = WeightUnit.LB;
};
stjs.extend(ExternalProductionWeight, null, [], function(constructor, prototype) {
    prototype.value = null;
    prototype.units = null;
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
    prototype.getUnits = function() {
        return this.units;
    };
    prototype.setUnits = function(units) {
        this.units = units;
    };
}, {value: "BigDecimal", units: {name: "Enum", arguments: ["WeightUnit"]}});

var ProductInstanceDetails = function() {};
stjs.extend(ProductInstanceDetails, null, [], function(constructor, prototype) {
    prototype.fileName = null;
    prototype.featureChoiceMap = null;
    prototype.type = null;
    prototype.getFileName = function() {
        return this.fileName;
    };
    prototype.setFileName = function(fileName) {
        this.fileName = fileName;
    };
    prototype.getFeatureChoiceMap = function() {
        return this.featureChoiceMap;
    };
    prototype.setFeatureChoiceMap = function(featureChoiceMap) {
        this.featureChoiceMap = featureChoiceMap;
    };
    prototype.getType = function() {
        return this.type;
    };
    prototype.setType = function(type) {
        this.type = type;
    };
}, {featureChoiceMap: {name: "Map", arguments: [null, null]}, type: {name: "Enum", arguments: ["ElementType"]}});

var ConditionException = function(msg, cause) {
    RuleConfigurationException.call(this, msg, cause);
};
stjs.extend(ConditionException, RuleConfigurationException, [], null, {});

var ValueException = function(message, cause) {
    RuleConfigurationException.call(this, message, cause);
};
stjs.extend(ValueException, RuleConfigurationException, [], null, {});

var ValidationResultMappings = function() {};
stjs.extend(ValidationResultMappings, null, [], function(constructor, prototype) {
    constructor.descMap = null;
    constructor.severityMap = new ArrayMap();
    constructor.getValidationDescByCode = function(code) {
        return ValidationResultMappings.descMap.get(code);
    };
    constructor.getValidationSeverityByCode = function(code) {
        return ValidationResultMappings.severityMap.get(code);
    };
}, {descMap: {name: "Map", arguments: [{name: "Enum", arguments: ["ValidationResultCode"]}, null]}, severityMap: {name: "Map", arguments: [{name: "Enum", arguments: ["ValidationResultCode"]}, {name: "Enum", arguments: ["ValidationSeverity"]}]}});
(function() {
    ValidationResultMappings.descMap = new ArrayMap();
    ValidationResultMappings.severityMap = new ArrayMap();
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_VALUE_REQUIRED, "Property value is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_VALUE_MIN_REQUIRED, "Property value is less than the minimum required {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_VALUE_MAX_EXCEEDED, "Property value is more than the maximum allowed {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_VALUE_NOT_ALLOWED, "Property value not in allowed values");
    ValidationResultMappings.descMap.put(ValidationResultCode.FEATURE_NOT_SELECTABLE, "Feature is not selectable");
    ValidationResultMappings.descMap.put(ValidationResultCode.CHOICE_NOT_SELECTABLE, "Choice is not selectable");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_NOT_ALLOWED, "Property is not allowed");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_VALUE_FORMAT_INVALID, "Property value format is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.FEATURE_REQUIRED, "Feature is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.CHOICE_REQUIRED, "Choice is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_REQUIRED, "Property is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.EXCEPTION_PAGE_RANGE_REQUIRED, "Page Range is required for Exception");
    ValidationResultMappings.descMap.put(ValidationResultCode.EXCEPTION_FEATURE_REQUIRED, "Feature is required for Exception");
    ValidationResultMappings.descMap.put(ValidationResultCode.EXCEPTION_START_PAGE_INVALID, "Start page number is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.EXCEPTION_END_PAGE_INVALID, "End page number is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.EXCEPTION_NOT_SELECTABLE, "Exception is not selectable");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PAGE_COUNT_MAX_EXCEEDED, "Page Count is more than the maximum allowed {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PAGE_COUNT_MIN_REQUIRED, "Page Count is less than the minimum required {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_FILE_SIZE_MAX_EXCEEDED, "File size is more than the maximum allowed {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, "More files can be uploaded");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PAGE_SIZE_INVALID, "Page size, {0} * [1}, is not valid for the product");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PAGE_SIZE_MIXED_NOT_ALLOWED, "Mixed page size is not allowed for the product");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_ORIENTATION_MIXED_NOT_ALLOWED, "Mixed orientation is not allowed for the product");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PRINT_READY_REQUIRED, "Product requires a print ready file");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_FILE_COUNT_MAX_EXCEEDED, "The file count is more than the maximum allowed, {0}");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_NOT_ALLOWED, "Content not allowed for a purpose in the product");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_ID_INVALID, "Invalid product id");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_ID_VERSION_INVALID, "Product data not available for id and version");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_PROCESSING_ERROR, "Product processing error");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_ID_REQUIRED, "Product id is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.FEATURE_ID_REQUIRED, "Feature id is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.CHOICE_ID_REQUIRED, "Choice id is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_ID_REQUIRED, "Property id is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_INTERNAL_ERROR, "Product input is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCT_QUANTITY_INVALID, "Product Quantity is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.FEATURE_ID_INVALID, "Feature id is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.CHOICE_ID_INVALID, "Choice id is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.PROPERTY_ID_INVALID, "Property id is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REQUIREMENT_ID_INVALID, "ContentRequirment id is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REQUIREMENT_ID_REQUIRED, "ContentRequirment id is Required");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REQUIREMENT_REQUIRED, "ContentRequirment is required");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REFERENCE_OR_PAGE_GROUP_REQUIRED, "Content should have either Page group or Content Reference Id");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_PAGE_GROUP_INVALID, "End Page number can not be less than Start Page number");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REQUIREMENT_PURPOSE_REQUIRED, "Content requirement purpose required");
    ValidationResultMappings.descMap.put(ValidationResultCode.CONTENT_REFERENCE_INVALID, "Content is Invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.DUPLICATE_PRODUCT_ELEMENT, "Duplicate product element");
    ValidationResultMappings.descMap.put(ValidationResultCode.PAGE_EXCEPTION_PAGE_RANGE_INVALID, "Page Exception Range Invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCTION_TIME_REQUIRED, "Fulfillment Time Required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCTION_WEIGHT_REQUIRED, "Fulfillment Weight Required");
    ValidationResultMappings.descMap.put(ValidationResultCode.PRODUCTION_TIME_INVALID, "Production Time is invalid");
    ValidationResultMappings.descMap.put(ValidationResultCode.WEIGHT_INVALID, "Weight is invalid");
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_VALUE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_VALUE_MIN_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_VALUE_MAX_EXCEEDED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_VALUE_NOT_ALLOWED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.FEATURE_NOT_SELECTABLE, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CHOICE_NOT_SELECTABLE, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_NOT_ALLOWED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_VALUE_FORMAT_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.FEATURE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CHOICE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.EXCEPTION_PAGE_RANGE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.EXCEPTION_FEATURE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.EXCEPTION_START_PAGE_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.EXCEPTION_END_PAGE_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.EXCEPTION_NOT_SELECTABLE, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PAGE_COUNT_MAX_EXCEEDED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PAGE_COUNT_MIN_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_FILE_SIZE_MAX_EXCEEDED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, ValidationSeverity.INFO);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PAGE_SIZE_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PAGE_SIZE_MIXED_NOT_ALLOWED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_ORIENTATION_MIXED_NOT_ALLOWED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PRINT_READY_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_FILE_COUNT_MAX_EXCEEDED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_NOT_ALLOWED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_ID_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_ID_VERSION_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_PROCESSING_ERROR, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_ID_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.FEATURE_ID_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CHOICE_ID_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_ID_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_INTERNAL_ERROR, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.FEATURE_ID_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CHOICE_ID_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PROPERTY_ID_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REQUIREMENT_ID_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REQUIREMENT_ID_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REQUIREMENT_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCT_QUANTITY_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REFERENCE_OR_PAGE_GROUP_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_PAGE_GROUP_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REQUIREMENT_PURPOSE_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.CONTENT_REFERENCE_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.DUPLICATE_PRODUCT_ELEMENT, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PAGE_EXCEPTION_PAGE_RANGE_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCTION_TIME_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCTION_WEIGHT_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCTION_TIME_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCTION_WEIGHT_REQUIRED, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.PRODUCTION_TIME_INVALID, ValidationSeverity.ERROR);
    ValidationResultMappings.severityMap.put(ValidationResultCode.WEIGHT_INVALID, ValidationSeverity.ERROR);
})();

var ExternalProductionTime = function() {
    this.units = TimeUnit.HOUR;
};
stjs.extend(ExternalProductionTime, null, [], function(constructor, prototype) {
    prototype.units = null;
    prototype.value = 0.0;
    prototype.getUnits = function() {
        return this.units;
    };
    prototype.setUnits = function(units) {
        this.units = units;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {units: {name: "Enum", arguments: ["TimeUnit"]}});

var PageType = function(pageNumber, pageExceptionType, range) {
    this.pageNumber = pageNumber;
    this.pageExceptionType = pageExceptionType;
    this.range = range;
};
stjs.extend(PageType, null, [], function(constructor, prototype) {
    prototype.pageNumber = null;
    prototype.pageExceptionType = null;
    prototype.range = null;
    prototype.getPageNumber = function() {
        return this.pageNumber;
    };
    prototype.setPageNumber = function(pageNumber) {
        this.pageNumber = pageNumber;
    };
    prototype.getPageExceptionType = function() {
        return this.pageExceptionType;
    };
    prototype.setPageExceptionType = function(pageExceptionType) {
        this.pageExceptionType = pageExceptionType;
    };
    prototype.getRange = function() {
        return this.range;
    };
    prototype.setRange = function(range) {
        this.range = range;
    };
    prototype.setPageType = function(pageNumber, pageExceptionType, range) {
        this.pageNumber = pageNumber;
        this.pageExceptionType = pageExceptionType;
        this.range = range;
    };
}, {pageExceptionType: {name: "Enum", arguments: ["PageExceptionType"]}, range: "PageRange"});

var FlattenedPageException = function() {};
stjs.extend(FlattenedPageException, null, [], function(constructor, prototype) {
    prototype.hasContent = false;
    prototype.ranges = null;
    prototype.properties = null;
    prototype.name = null;
    prototype.isHasContent = function() {
        return this.hasContent;
    };
    prototype.setHasContent = function(hasContent) {
        this.hasContent = hasContent;
    };
    prototype.getRanges = function() {
        return this.ranges;
    };
    prototype.setRanges = function(ranges) {
        this.ranges = ranges;
    };
    prototype.getProperties = function() {
        return this.properties;
    };
    prototype.setProperties = function(properties) {
        this.properties = properties;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
}, {ranges: {name: "List", arguments: ["PageRange"]}, properties: {name: "Map", arguments: [null, null]}});

var PageRangeProcessor = function() {};
stjs.extend(PageRangeProcessor, null, [], function(constructor, prototype) {
    constructor.splitpage = function(context, elementType) {
        var productPageRange = new ArrayList();
        if (elementType != null && elementType == ElementType.PAGEEXCEPTION) {
            productPageRange = context.getPageException().getRanges();
        } else {
            var cas = context.getProduct().getContentAssociations();
            var it = cas.iterator();
            var pageGroupIt = null;
            var pageGroup = null;
            var pageCount = 0;
             while (it.hasNext()){
                pageGroupIt = it.next().getPageGroups().iterator();
                 while (pageGroupIt.hasNext()){
                    pageGroup = pageGroupIt.next();
                    pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart() + 1);
                }
            }
            var pageExceptions = context.getProduct().getPageExceptions();
            var peIt = pageExceptions.iterator();
            var pageExceptionInstance = null;
            var pageRange;
             while (peIt.hasNext()){
                pageExceptionInstance = peIt.next();
                var peiIt = pageExceptionInstance.getProperties().iterator();
                var pageRangeIt;
                 while (peiIt.hasNext()){
                    var pr = peiIt.next();
                    if (pr.getValue().equals("INSERT") || pr.getValue().equals("TAB")) {
                        pageRangeIt = pageExceptionInstance.getRanges().iterator();
                         while (pageRangeIt.hasNext()){
                            pageRange = pageRangeIt.next();
                            pageCount = pageCount + (pageRange.getEnd() - pageRange.getStart() + 1);
                        }
                    }
                }
            }
            var contentRange = new PageRange();
            contentRange.setStart(1);
            contentRange.setEnd(pageCount);
            var sortedPageExceptions = new ArrayList();
            var pgeIt = context.getProduct().getPageExceptions().iterator();
             while (pgeIt.hasNext()){
                var pei = pgeIt.next();
                var rangeIt = pei.getRanges().iterator();
                 while (rangeIt.hasNext()){
                    var index = 0;
                    var range = rangeIt.next();
                    for (var i = 0; i < sortedPageExceptions.size(); i++) {
                        var start = sortedPageExceptions.get(i).getStart();
                        if (range.getStart() < start) {
                            break;
                        }
                        index++;
                    }
                    Utils.addRangeToList(index, range, sortedPageExceptions);
                }
            }
            var speIt = sortedPageExceptions.iterator();
             while (speIt.hasNext()){
                var range = speIt.next();
                if (range.getStart() != contentRange.getStart()) {
                    var erange = new PageRange();
                    erange.setStart(contentRange.getStart());
                    erange.setEnd(range.getStart() - 1);
                    productPageRange.add(erange);
                }
                contentRange.setStart(range.getEnd() + 1);
            }
            if (contentRange.getStart() <= pageCount) {
                productPageRange.add(contentRange);
            }
        }
        return productPageRange;
    };
}, {});

/**
 *  Rule which returns true or false based on evaluation of the contained Condition
 *  @author cbochman
 */
var BooleanRuleDef = function() {};
stjs.extend(BooleanRuleDef, null, [RuleDefinition], function(constructor, prototype) {
    prototype.condition = null;
    /**
     *  Evaluate the contained condition
     *  @param context 
     *  @return true or false based on evaluation of the contained Condition
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluate = function(context) {
        if (this.condition != null) 
            return this.condition.evaluate(context);
        return false;
    };
    prototype.getCondition = function() {
        return this.condition;
    };
    prototype.setCondition = function(condition) {
        this.condition = condition;
    };
}, {condition: "Condition"});

/**
 *  Rule which returns a validation result if its condition evaluates to true
 *  @author cbochman
 */
var ValidationRuleDef = function() {};
stjs.extend(ValidationRuleDef, null, [RuleDefinition], function(constructor, prototype) {
    /**
     * If this condition evaluates to true, the resultProvider will be evaluated to return a ValidationResult 
     */
    prototype.condition = null;
    /**
     * Responsible for building a ValidationResult if the condition evaluates to true 
     */
    prototype.resultProvider = null;
    /**
     *  Evaluate the Condition and possibly return a ValidationResult provided by the ValidationResultProvider
     *  @param context
     *  @return ValidationResult if the condition evaluates to true, null otherwise
     *  @throws ValueException
     *  @throws ConditionException
     */
    prototype.evaluate = function(context) {
        var result = null;
        if (this.condition != null) {
            if (this.condition.evaluate(context)) {
                if (this.resultProvider != null) 
                    result = this.resultProvider.getResult(context);
            }
        }
        return result;
    };
    prototype.getCondition = function() {
        return this.condition;
    };
    prototype.setCondition = function(condition) {
        this.condition = condition;
    };
    prototype.getResultProvider = function() {
        return this.resultProvider;
    };
    prototype.setResultProvider = function(resultProvider) {
        this.resultProvider = resultProvider;
    };
}, {condition: "Condition", resultProvider: "ValidationResultProvider"});

/**
 *  Rule which returns true or false based on evaluation of the contained Condition
 *  @author cbochman
 */
var OrderedBooleanRuleDef = function() {};
stjs.extend(OrderedBooleanRuleDef, null, [RuleDefinition], function(constructor, prototype) {
    prototype.sequence = null;
    prototype.condition = null;
    /**
     *  Evaluate the contained condition
     *  @param context 
     *  @return true or false based on evaluation of the contained Condition
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluate = function(context) {
        if (this.condition != null) 
            return this.condition.evaluate(context);
        return false;
    };
    prototype.getSequence = function() {
        return this.sequence;
    };
    prototype.setSequence = function(sequence) {
        this.sequence = sequence;
    };
    prototype.getCondition = function() {
        return this.condition;
    };
    prototype.setCondition = function(condition) {
        this.condition = condition;
    };
}, {condition: "Condition"});

var ValidationResult = function() {
    AbstractRuleElement.call(this);
    this.refIds = new ArrayList();
};
stjs.extend(ValidationResult, AbstractRuleElement, [], function(constructor, prototype) {
    prototype.code = null;
    prototype.desc = null;
    prototype.severity = null;
    prototype.refIds = null;
    prototype.elementType = null;
    prototype.elementIndex = 0;
    prototype.elementInstanceId = null;
    prototype.getCode = function() {
        return this.code;
    };
    prototype.setCode = function(code) {
        this.code = code;
    };
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.getSeverity = function() {
        return this.severity;
    };
    prototype.setSeverity = function(severity) {
        this.severity = severity;
    };
    prototype.getRefIds = function() {
        return this.refIds;
    };
    prototype.setRefIds = function(refIds) {
        this.refIds = refIds;
    };
    prototype.getElementType = function() {
        return this.elementType;
    };
    prototype.setElementType = function(elementType) {
        this.elementType = elementType;
    };
    prototype.getElementIndex = function() {
        return this.elementIndex;
    };
    prototype.setElementIndex = function(elementIndex) {
        this.elementIndex = elementIndex;
    };
    prototype.getElementInstanceId = function() {
        return this.elementInstanceId;
    };
    prototype.setElementInstanceId = function(elementInstanceId) {
        this.elementInstanceId = elementInstanceId;
    };
}, {severity: {name: "Enum", arguments: ["ValidationSeverity"]}, refIds: {name: "List", arguments: [null]}, elementType: {name: "Enum", arguments: ["ElementType"]}});

var AbstractValueProvider = function() {
    AbstractRuleElement.call(this);
    this.type = ValueType.STRING;
};
stjs.extend(AbstractValueProvider, AbstractRuleElement, [ValueProvider], function(constructor, prototype) {
    prototype.type = null;
    prototype.getType = function() {
        return this.type;
    };
    prototype.setType = function(type) {
        this.type = type;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContentAddedCondition = function() {
    AbstractRuleElement.call(this);
};
stjs.extend(ContentAddedCondition, AbstractRuleElement, [Condition], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        return context.isContentAdded();
    };
}, {});

var ConditionalValue = function() {
    AbstractRuleElement.call(this);
    this.condition = null;
    this.valueProvider = null;
};
stjs.extend(ConditionalValue, AbstractRuleElement, [], function(constructor, prototype) {
    prototype.condition = null;
    prototype.valueProvider = null;
    prototype.getCondition = function() {
        return this.condition;
    };
    prototype.setCondition = function(condition) {
        this.condition = condition;
    };
    prototype.getValueProvider = function() {
        return this.valueProvider;
    };
    prototype.setValueProvider = function(valueProvider) {
        this.valueProvider = valueProvider;
    };
}, {condition: "Condition", valueProvider: "ValueProvider"});

var AbstractValidationResultProvider = function() {
    AbstractRuleElement.call(this);
    this.refIds = new ArrayList();
};
stjs.extend(AbstractValidationResultProvider, AbstractRuleElement, [ValidationResultProvider], function(constructor, prototype) {
    prototype.severity = null;
    prototype.type = null;
    prototype.refIds = null;
    prototype.getResult = function(context) {
        return null;
    };
    prototype.getSeverity = function() {
        return this.severity;
    };
    prototype.setSeverity = function(severity) {
        this.severity = severity;
    };
    prototype.getType = function() {
        return this.type;
    };
    prototype.setType = function(type) {
        this.type = type;
    };
    prototype.getRefIds = function() {
        return this.refIds;
    };
    prototype.setRefIds = function(refIds) {
        this.refIds = refIds;
    };
}, {severity: {name: "Enum", arguments: ["ValidationSeverity"]}, type: {name: "Enum", arguments: ["ValidationType"]}, refIds: {name: "List", arguments: [null]}});

var AbstractCondition = function() {
    AbstractRuleElement.call(this);
    this.booleanOperator = BooleanOperator.AND;
};
stjs.extend(AbstractCondition, AbstractRuleElement, [Condition], function(constructor, prototype) {
    prototype.booleanOperator = null;
    prototype.getBooleanOperator = function() {
        return this.booleanOperator;
    };
    prototype.setBooleanOperator = function(operator) {
        this.booleanOperator = operator;
    };
}, {booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

/**
 *  Condition which always evaluates to true
 *  @author cbochman
 */
var TrueCondition = function() {
    AbstractRuleElement.call(this);
};
stjs.extend(TrueCondition, AbstractRuleElement, [Condition], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        return true;
    };
}, {});

/**
 *  Copyright (c) 2020 Fedex. All Rights Reserved.<br>
 * 
 *  Feature - Rate Service PI20.5: Implementation of cart.<br>
 *  User Story - B-359505 Create Request DTOs for Save Cart Request in Rating
 *  Service.<br>
 * 
 *  Description: This is a VendorReference class for RateAndSave Request and
 *  Response.<br>
 * 
 *  @author Rinkal Singh Tomar[3905909]
 *  @since April 13, 2020
 *  @version 1.0
 */
var VendorReference = function() {};
stjs.extend(VendorReference, null, [], function(constructor, prototype) {
    prototype.vendorId = null;
    prototype.vendorProductName = null;
    prototype.vendorProductDesc = null;
    prototype.references = null;
    prototype.getVendorId = function() {
        return this.vendorId;
    };
    prototype.setVendorId = function(vendorId) {
        this.vendorId = vendorId;
    };
    prototype.getVendorProductName = function() {
        return this.vendorProductName;
    };
    prototype.setVendorProductName = function(vendorProductName) {
        this.vendorProductName = vendorProductName;
    };
    prototype.getVendorProductDesc = function() {
        return this.vendorProductDesc;
    };
    prototype.setVendorProductDesc = function(vendorProductDesc) {
        this.vendorProductDesc = vendorProductDesc;
    };
    prototype.getReferences = function() {
        return this.references;
    };
    prototype.setReferences = function(references) {
        this.references = references;
    };
}, {references: {name: "List", arguments: ["Reference"]}});

var Preset = function() {
    this.choices = new ArraySet();
};
stjs.extend(Preset, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.sequence = 0;
    prototype.qty = 0;
    prototype.choices = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getSequence = function() {
        return this.sequence;
    };
    prototype.setSequence = function(sequence) {
        this.sequence = sequence;
    };
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.getChoices = function() {
        return this.choices;
    };
    prototype.setChoices = function(choices) {
        this.choices = choices;
    };
}, {choices: {name: "Set", arguments: ["PresetChoice"]}});

var AbstractPageException = function() {
    ProductElement.call(this);
    this.features = new ArraySet();
    this.properties = new ArraySet();
};
stjs.extend(AbstractPageException, ProductElement, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.features = null;
    prototype.properties = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getFeatures = function() {
        return this.features;
    };
    prototype.setFeatures = function(features) {
        this.features = features;
    };
    prototype.getProperties = function() {
        return this.properties;
    };
    prototype.setProperties = function(properties) {
        this.properties = properties;
    };
}, {features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}});

var AbstractProperty = function() {
    ProductElement.call(this);
};
stjs.extend(AbstractProperty, ProductElement, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.value = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {});

var AbstractProduct = function() {
    ProductElement.call(this);
    this.features = new ArraySet();
    this.properties = new ArraySet();
    this.pageExceptions = new ArrayList();
};
stjs.extend(AbstractProduct, ProductElement, [], function(constructor, prototype) {
    prototype.version = 0;
    prototype.name = null;
    prototype.qty = 0;
    prototype.priceable = false;
    prototype.features = null;
    prototype.properties = null;
    prototype.pageExceptions = null;
    prototype.proofRequired = false;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.getFeatures = function() {
        return this.features;
    };
    prototype.setFeatures = function(features) {
        this.features = features;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getProperties = function() {
        return this.properties;
    };
    prototype.setProperties = function(properties) {
        this.properties = properties;
    };
    prototype.getPageExceptions = function() {
        return this.pageExceptions;
    };
    prototype.setPageExceptions = function(pageExceptions) {
        this.pageExceptions = pageExceptions;
    };
    prototype.isProofRequired = function() {
        return this.proofRequired;
    };
    prototype.setProofRequired = function(proofRequired) {
        this.proofRequired = proofRequired;
    };
    prototype.getPriceable = function() {
        return this.priceable;
    };
    prototype.setPriceable = function(priceable) {
        this.priceable = priceable;
    };
}, {features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}, pageExceptions: {name: "List", arguments: ["Pe"]}});

var AbstractFeature = function() {
    ProductElement.call(this);
};
stjs.extend(AbstractFeature, ProductElement, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
}, {});

var Template = function() {
    ProductElement.call(this);
    this.choiceIds = new ArrayList();
};
stjs.extend(Template, ProductElement, [], function(constructor, prototype) {
    prototype.templateId = null;
    prototype.choiceIds = null;
    prototype.getTemplateId = function() {
        return this.templateId;
    };
    prototype.setTemplateId = function(templateId) {
        this.templateId = templateId;
    };
    prototype.getChoiceIds = function() {
        return this.choiceIds;
    };
    prototype.setChoiceIds = function(choiceIds) {
        this.choiceIds = choiceIds;
    };
}, {choiceIds: {name: "List", arguments: [null]}});

var AbstractChoice = function() {
    ProductElement.call(this);
    this.properties = new ArraySet();
};
stjs.extend(AbstractChoice, ProductElement, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.properties = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getProperties = function() {
        return this.properties;
    };
    prototype.setProperties = function(properties) {
        this.properties = properties;
    };
}, {properties: {name: "Set", arguments: ["P"]}});

var CompatibilityGroup = function() {
    this.compatibilitySubGroups = new ArraySet();
};
stjs.extend(CompatibilityGroup, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.compatibilitySubGroups = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getCompatibilitySubGroups = function() {
        return this.compatibilitySubGroups;
    };
    prototype.setCompatibilitySubGroups = function(compatibilitySubGroups) {
        this.compatibilitySubGroups = compatibilitySubGroups;
    };
}, {compatibilitySubGroups: {name: "Set", arguments: ["CompatibilitySubGroup"]}});

var PageGroup = function() {
    PageRange.call(this);
};
stjs.extend(PageGroup, PageRange, [], function(constructor, prototype) {
    prototype.width = 0.0;
    prototype.height = 0.0;
    prototype.orientation = null;
    prototype.getWidth = function() {
        return this.width;
    };
    prototype.setWidth = function(width) {
        this.width = width;
    };
    prototype.getHeight = function() {
        return this.height;
    };
    prototype.setHeight = function(height) {
        this.height = height;
    };
    prototype.getOrientation = function() {
        return this.orientation;
    };
    prototype.setOrientation = function(orientation) {
        this.orientation = orientation;
    };
}, {orientation: {name: "Enum", arguments: ["ContentOrientation"]}});

var Bound = function() {
    this.allowedValues = new ArraySet();
};
stjs.extend(Bound, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.measure = null;
    prototype.min = null;
    prototype.max = null;
    prototype.type = null;
    prototype.allowedValues = null;
    prototype.expression = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getMeasure = function() {
        return this.measure;
    };
    prototype.setMeasure = function(measure) {
        this.measure = measure;
    };
    prototype.getMin = function() {
        return this.min;
    };
    prototype.setMin = function(min) {
        this.min = min;
    };
    prototype.getMax = function() {
        return this.max;
    };
    prototype.setMax = function(max) {
        this.max = max;
    };
    prototype.getType = function() {
        return this.type;
    };
    prototype.setType = function(type) {
        this.type = type;
    };
    prototype.getAllowedValues = function() {
        return this.allowedValues;
    };
    prototype.setAllowedValues = function(allowedValues) {
        this.allowedValues = allowedValues;
    };
    prototype.getExpression = function() {
        return this.expression;
    };
    prototype.setExpression = function(expression) {
        this.expression = expression;
    };
}, {type: {name: "Enum", arguments: ["DataType"]}, allowedValues: {name: "Set", arguments: ["PropertyAllowedValue"]}});

var BleedDimension = function() {};
stjs.extend(BleedDimension, null, [], function(constructor, prototype) {
    prototype.width = null;
    prototype.height = null;
    prototype.getWidth = function() {
        return this.width;
    };
    prototype.setWidth = function(width) {
        this.width = width;
    };
    prototype.getHeight = function() {
        return this.height;
    };
    prototype.setHeight = function(height) {
        this.height = height;
    };
}, {width: "BleedRange", height: "BleedRange"});

var DisplayVersion = function() {};
stjs.extend(DisplayVersion, null, [], function(constructor, prototype) {
    prototype.version = 0;
    prototype.start = null;
    prototype.end = null;
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getStart = function() {
        return this.start;
    };
    prototype.setStart = function(start) {
        this.start = start;
    };
    prototype.getEnd = function() {
        return this.end;
    };
    prototype.setEnd = function(end) {
        this.end = end;
    };
}, {start: "Date", end: "Date"});

var ElementDisplay = function() {
    this.displayTexts = new ArrayList();
    this.displayHints = new ArrayList();
    this.controlIds = new ArrayList();
};
stjs.extend(ElementDisplay, null, [], function(constructor, prototype) {
    prototype.controlIds = null;
    prototype.sequence = 0;
    prototype.name = null;
    prototype.img = null;
    prototype.tooltipText = null;
    prototype.parentId = null;
    prototype.displayHints = null;
    prototype.displayTexts = null;
    prototype.disabledTexts = null;
    prototype.getSequence = function() {
        return this.sequence;
    };
    prototype.setSequence = function(sequence) {
        this.sequence = sequence;
    };
    prototype.getControlIds = function() {
        return this.controlIds;
    };
    prototype.setControlIds = function(controlIds) {
        this.controlIds = controlIds;
    };
    prototype.addControlId = function(id) {
        this.controlIds.add(id);
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getImg = function() {
        return this.img;
    };
    prototype.setImg = function(img) {
        this.img = img;
    };
    prototype.getTooltipText = function() {
        return this.tooltipText;
    };
    prototype.setTooltipText = function(tooltipText) {
        this.tooltipText = tooltipText;
    };
    prototype.getParentId = function() {
        return this.parentId;
    };
    prototype.setParentId = function(parentId) {
        this.parentId = parentId;
    };
    prototype.getDisplayHints = function() {
        return this.displayHints;
    };
    prototype.setDisplayHints = function(displayHints) {
        this.displayHints = displayHints;
    };
    prototype.addDisplayHint = function(hint) {
        this.displayHints.add(hint);
    };
    prototype.getDisplayTexts = function() {
        return this.displayTexts;
    };
    prototype.setDisplayTexts = function(displayTexts) {
        this.displayTexts = displayTexts;
    };
    prototype.addDisplayText = function(d) {
        this.displayTexts.add(d);
    };
    prototype.getDisabledTexts = function() {
        return this.disabledTexts;
    };
    prototype.setDisabledTexts = function(disabledTexts) {
        this.disabledTexts = disabledTexts;
    };
}, {controlIds: {name: "List", arguments: [null]}, displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}});

var DisplayDetails = function() {
    this.displayTexts = new ArrayList();
    this.displayHints = new ArrayList();
    this.controlIds = new ArrayList();
    this.displays = new ArrayList();
};
stjs.extend(DisplayDetails, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.refId = null;
    prototype.name = null;
    prototype.img = null;
    prototype.tooltipText = null;
    prototype.controlId = null;
    prototype.parentId = null;
    prototype.displayHints = null;
    prototype.displayTexts = null;
    prototype.disabledTexts = null;
    prototype.controlIds = null;
    prototype.displays = null;
    prototype.hashCode = function() {
        return this.id.hashCode();
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getTooltipText = function() {
        return this.tooltipText;
    };
    prototype.setTooltipText = function(tooltipText) {
        this.tooltipText = tooltipText;
    };
    prototype.getControlId = function() {
        return this.controlId;
    };
    prototype.setControlId = function(controlId) {
        this.controlId = controlId;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getRefId = function() {
        return this.refId;
    };
    prototype.setRefId = function(refId) {
        this.refId = refId;
    };
    prototype.getImg = function() {
        return this.img;
    };
    prototype.setImg = function(img) {
        this.img = img;
    };
    prototype.getDisplayTexts = function() {
        return this.displayTexts;
    };
    prototype.setDisplayTexts = function(displayTexts) {
        this.displayTexts = displayTexts;
    };
    prototype.getDisplayHints = function() {
        return this.displayHints;
    };
    prototype.setDisplayHints = function(displayHints) {
        this.displayHints = displayHints;
    };
    prototype.addDisplayText = function(d) {
        this.displayTexts.add(d);
    };
    prototype.addDisplayTexts = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addDisplayText(it.next());
        }
    };
    prototype.removeDisplayTexts = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.removeDisplayText(it.next());
        }
    };
    prototype.removeDisplayText = function(d) {
        var index = this.displayTexts.indexOf(d);
        this.displayTexts.remove(index);
    };
    prototype.addDisplayHint = function(hint) {
        this.displayHints.add(hint);
    };
    prototype.getParentId = function() {
        return this.parentId;
    };
    prototype.setParentId = function(displayParentId) {
        this.parentId = displayParentId;
    };
    prototype.getDisabledTexts = function() {
        return this.disabledTexts;
    };
    prototype.setDisabledTexts = function(disabledTexts) {
        this.disabledTexts = disabledTexts;
    };
    prototype.getControlIds = function() {
        return this.controlIds;
    };
    prototype.setControlIds = function(controlIds) {
        this.controlIds = controlIds;
    };
    prototype.addControlIds = function(ids) {
        this.controlIds.add(ids);
    };
    prototype.getDisplays = function() {
        return this.displays;
    };
    prototype.setDisplays = function(displays) {
        this.displays = displays;
    };
    prototype.addDisplay = function(display) {
        this.displays.add(display);
    };
}, {displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var Rule = function() {
    AbstractRuleElement.call(this);
};
stjs.extend(Rule, AbstractRuleElement, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.type = null;
    /**
     * Id of entity to which this rule applies 
     */
    prototype.refId = null;
    prototype.def = null;
    prototype.evaluate = function(context) {
        if (this.def != null) {
            return this.def.evaluate(context);
        }
        return null;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getType = function() {
        return this.type;
    };
    prototype.setType = function(type) {
        this.type = type;
    };
    prototype.getRefId = function() {
        return this.refId;
    };
    prototype.setRefId = function(refId) {
        this.refId = refId;
    };
    prototype.getDef = function() {
        return this.def;
    };
    prototype.setDef = function(def) {
        this.def = def;
    };
}, {type: "RuleType", def: "RuleDefinition"});

/**
 *  Mapping of RuleType to RuleClass
 *  Any new rule types or classes will need to be added here for now
 *  This will eventually be in a config file to be used by both the java and javascript libraries
 *  @author cbochman
 */
var RuleMappings = function() {};
stjs.extend(RuleMappings, null, [], function(constructor, prototype) {
    constructor.ruleTypeClassNameMap = null;
    constructor.getRuleClassNameByType = function(type) {
        return RuleMappings.ruleTypeClassNameMap.get(type.getId());
    };
}, {ruleTypeClassNameMap: {name: "Map", arguments: [null, null]}});
(function() {
    RuleMappings.ruleTypeClassNameMap = new ArrayMap();
    RuleMappings.ruleTypeClassNameMap.put(RuleType.VALUE.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.ASSOCIATE_TEXT.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.SKU_CODE.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.SKU_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.SKU_UNIT_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.SKU_DSC_LOOKUP_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.DEFAULT_SELECTION.getId(), "com.fedex.office.cs.product.rules.BooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.AVAILABLE.getId(), "com.fedex.office.cs.product.rules.BooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.REQUIRED.getId(), "com.fedex.office.cs.product.rules.BooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.VALIDATION.getId(), "com.fedex.office.cs.product.rules.ValidationRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PRICEABLE.getId(), "com.fedex.office.cs.product.rules.BooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.OVERRIDE_DEFAULT.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.DEFAULT_OVERRIDE.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.OVERRIDE_DEFAULT_FLAG.getId(), "com.fedex.office.cs.product.rules.OrderedBooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.SKU_REFERENCE.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PROOF_FLAG.getId(), "com.fedex.office.cs.product.rules.BooleanRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PRODUCT_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.WEIGHT_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.DLT_EXPRESSION.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PROD_TIME_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PRODUCTION_QTY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PRODUCTION_CAPABILITY.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.PRIORITY_TIME.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.NFC_TIME.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
    RuleMappings.ruleTypeClassNameMap.put(RuleType.STANDARD_TIME.getId(), "com.fedex.office.cs.product.rules.ValueRuleDef");
})();

var SkuDisplayGroups = function() {};
stjs.extend(SkuDisplayGroups, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.skus = null;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getSkus = function() {
        return this.skus;
    };
    prototype.setSkus = function(skus) {
        this.skus = skus;
    };
}, {skus: {name: "List", arguments: ["ExternalSku"]}});

var ProductInstanceSummary = function() {};
stjs.extend(ProductInstanceSummary, null, [], function(constructor, prototype) {
    prototype.productName = null;
    prototype.userProductName = null;
    prototype.details = null;
    prototype.getProductName = function() {
        return this.productName;
    };
    prototype.setProductName = function(productName) {
        this.productName = productName;
    };
    prototype.getDetails = function() {
        return this.details;
    };
    prototype.setDetails = function(details) {
        this.details = details;
    };
    prototype.getUserProductName = function() {
        return this.userProductName;
    };
    prototype.setUserProductName = function(userProductName) {
        this.userProductName = userProductName;
    };
}, {details: {name: "List", arguments: ["ProductInstanceDetails"]}});

var UnsupportedOperatorException = function(msg, cause) {
    ConditionException.call(this, msg, cause);
};
stjs.extend(UnsupportedOperatorException, ConditionException, [], null, {});

var ValueConverter = function() {};
stjs.extend(ValueConverter, null, [], function(constructor, prototype) {
    constructor.getTypeRank = function(type) {
        switch (type) {
            case ValueType.STRING:
                return 0;
            case ValueType.BOOLEAN:
                return 1;
            case ValueType.DECIMAL:
                return 2;
            case ValueType.INTEGER:
                return 3;
            case ValueType.LONG:
                return 4;
            default:
                return 5;
        }
    };
    constructor.getHighestType = function(type1, type2) {
        if (ValueConverter.getTypeRank(type1) < ValueConverter.getTypeRank(type2)) 
            return type1;
        return type2;
    };
    constructor.convertToType = function(inValue, fromType, toType) {
        var outValue = null;
        if (inValue != null) {
            switch (toType) {
                case ValueType.BOOLEAN:
                    outValue = ValueConverter.convertToBoolean(inValue, fromType);
                    break;
                case ValueType.DECIMAL:
                    outValue = ValueConverter.convertToDecimal(inValue, fromType);
                    break;
                case ValueType.INTEGER:
                    outValue = ValueConverter.convertToInteger(inValue, fromType);
                    break;
                case ValueType.STRING:
                    outValue = ValueConverter.convertToString(inValue, fromType);
                    break;
                case ValueType.LONG:
                    outValue = ValueConverter.convertToLong(inValue, fromType);
                    break;
                default:
                     throw new ValueException("Unsupported destination value type: " + toType, null);
            }
        }
        return outValue;
    };
    constructor.convertToInteger = function(inValue, fromType) {
        var outValue = null;
        if (inValue != null) {
            try {
                switch (fromType) {
                    case ValueType.BOOLEAN:
                        var bv = inValue;
                        if (bv) 
                            outValue = 1;
                         else 
                            outValue = 0;
                        break;
                    case ValueType.INTEGER:
                        outValue = inValue;
                        break;
                    case ValueType.STRING:
                        outValue = new Integer(inValue);
                        break;
                    case ValueType.DECIMAL:
                        outValue = new Integer(stjs.trunc(Math.ceil(Double.parseDouble(inValue.toString()))));
                        break;
                    default:
                         throw new ValueException("Cannot convert type " + fromType + " to INTEGER", null);
                }
            }catch (ex) {
                 throw new ValueException("Value (" + inValue + ") was not of specified ValueType " + fromType, ex);
            }
        }
        return outValue;
    };
    constructor.convertToDecimal = function(inValue, fromType) {
        var outValue = null;
        if (inValue != null) {
            try {
                switch (fromType) {
                    case ValueType.BOOLEAN:
                        var bv = inValue;
                        if (bv) 
                            outValue = BigDecimal.ZERO;
                         else 
                            outValue = BigDecimal.ONE;
                        break;
                    case ValueType.DECIMAL:
                        outValue = BigDecimal.valueOf(Double.parseDouble(inValue.toString()));
                        break;
                    case ValueType.INTEGER:
                        outValue = BigDecimal.valueOf((inValue).longValue());
                        break;
                    case ValueType.STRING:
                        outValue = new BigDecimal(inValue);
                        break;
                    default:
                         throw new ValueException("Cannot convert type " + fromType + " to DECIMAL", null);
                }
            }catch (ex) {
                 throw new ValueException("Value (" + inValue + ") was not of specified ValueType " + fromType, ex);
            }
        }
        return outValue;
    };
    constructor.convertToString = function(inValue, fromType) {
        var outValue = null;
        if (inValue != null) {
            try {
                switch (fromType) {
                    case ValueType.DECIMAL:
                        outValue = (inValue).toPlainString();
                        break;
                    default:
                        outValue = inValue.toString();
                }
            }catch (ex) {
                 throw new ValueException("Value (" + inValue + ") was not of specified ValueType " + fromType, ex);
            }
        }
        return outValue;
    };
    constructor.convertToBoolean = function(inValue, fromType) {
        var outValue = null;
        if (inValue != null) {
            try {
                switch (fromType) {
                    case ValueType.BOOLEAN:
                        outValue = inValue;
                        break;
                    case ValueType.STRING:
                        outValue = Utils.convertToBoolean(inValue);
                        break;
                    default:
                         throw new ValueException("Cannot convert type " + fromType + " to BOOLEAN", null);
                }
            }catch (ex) {
                 throw new ValueException("Value (" + inValue + ") was not of specified ValueType " + fromType, ex);
            }
        }
        return outValue;
    };
    constructor.convertToLong = function(inValue, fromType) {
        var outValue = null;
        if (inValue != null) {
            try {
                switch (fromType) {
                    case ValueType.BOOLEAN:
                        var bv = inValue;
                        if (bv) 
                            outValue = new Long(1);
                         else 
                            outValue = new Long(0);
                        break;
                    case ValueType.INTEGER:
                        outValue = ((inValue).longValue());
                        break;
                    case ValueType.STRING:
                        outValue = new Long(inValue);
                        break;
                    case ValueType.LONG:
                        outValue = inValue;
                        break;
                    default:
                         throw new ValueException("Cannot convert type " + fromType + " to INTEGER", null);
                }
            }catch (ex) {
                 throw new ValueException("Value (" + inValue + ") was not of specified ValueType " + fromType, ex);
            }
        }
        return outValue;
    };
}, {});

var ExternalProductionDetails = function() {};
stjs.extend(ExternalProductionDetails, null, [], function(constructor, prototype) {
    prototype.weight = null;
    prototype.productionTime = null;
    prototype.getWeight = function() {
        return this.weight;
    };
    prototype.setWeight = function(weight) {
        this.weight = weight;
    };
    prototype.getProductionTime = function() {
        return this.productionTime;
    };
    prototype.setProductionTime = function(productionTime) {
        this.productionTime = productionTime;
    };
}, {weight: "ExternalProductionWeight", productionTime: "ExternalProductionTime"});

var PageExceptionIdProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageExceptionIdProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        return context.getPageException().getId();
    };
    prototype.getType = function() {
        return ValueType.LONG;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var FeatureChoiceNameProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(FeatureChoiceNameProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.featureId = null;
    prototype.getFeatureId = function() {
        return this.featureId;
    };
    prototype.setFeatureId = function(featureId) {
        this.featureId = featureId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        return context.getProduct().getFeatureById(this.featureId).getChoice().getName();
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContentUrlProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContentUrlProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var contentAssociation = context.getValue("ARRAY_OBJ");
        if (contentAssociation != null && contentAssociation.getContentReference() != null) {
            return context.getValue("SERVICE_URI") + "/globalaccess/" + contentAssociation.getContentReference() + "/12345678910/" + UUID.randomUUID().toString() + ".pdf?requestType=8&sid=1&org=Kinkos+Production+Location";
        }
        return null;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

/**
 *  
 *  This is called before PageException is added to ProductInstance.UI will call
 *  validatePageException to validate if page position choosen is a valid or
 *  not.If its not valid validationResults will be send back.
 */
var PageExceptionValidationProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageExceptionValidationProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.allowedPageRange = null;
    prototype.pageExceptionIdList = null;
    constructor.allowedRange = "EVEN";
    constructor.validationMsg = "PAGE_EXCEPTION_PAGE_RANGE_INVALID";
    prototype.getAllowedPageRange = function() {
        return this.allowedPageRange;
    };
    prototype.setAllowedPageRange = function(allowedPageRange) {
        this.allowedPageRange = allowedPageRange;
    };
    prototype.getPageExceptionIdList = function() {
        return this.pageExceptionIdList;
    };
    prototype.setPageExceptionIdList = function(pageExceptionIdList) {
        this.pageExceptionIdList = pageExceptionIdList;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var totalPageCount = this.getTotalPageCount(context.getProduct().getContentAssociations());
        var startPage = this.getStartPage(context.getProduct().getContentAssociations());
        if (this.allowedPageRange != null) {
            if (this.allowedPageRange.equals(PageExceptionValidationProvider.allowedRange)) {
                startPage = startPage + 1;
            }
            return this.validatePageRangeList(totalPageCount, context, startPage);
        } else {
            return this.validatePagePosition(totalPageCount, context);
        }
    };
    /**
     *  This method is called to validate the pageExeption page range.
     *  
     *  @param totalPageCount
     *  @param peInstanceList
     *  @param startPage
     *  @return
     */
    prototype.validatePageRangeList = function(totalPageCount, context, startPage) {
        var pageExceptionsRange = new ArrayList();
        var validationError = null;
        var allowedRangeList = this.initialValidPageExceptionList(totalPageCount, startPage);
        var peInstanceList = context.getProduct().getPageExceptions();
        var peInstanceItr = peInstanceList.iterator();
        var peInstance = null;
        var addPageException = false;
        var exceptionPage = 0;
        var invalidPages = new ArrayList();
        context.setRefIds(new ArrayList());
         while (peInstanceItr.hasNext()){
            peInstance = peInstanceItr.next();
            if (this.pageExceptionIdList.contains(peInstance.getId())) {
                addPageException = false;
                var range = peInstance.getRanges();
                var pageRangeItr = range.iterator();
                 while (pageRangeItr.hasNext()){
                    var pageRange = pageRangeItr.next();
                    exceptionPage = pageRange.getStart();
                    if (allowedRangeList.contains(exceptionPage) && !pageExceptionsRange.contains(exceptionPage)) {
                        addPageException = true;
                        pageExceptionsRange.add(exceptionPage);
                        break;
                    }
                }
                if (addPageException) {
                    allowedRangeList = this.rebuildValidPageExceptionList(startPage, allowedRangeList, exceptionPage, totalPageCount, pageExceptionsRange);
                } else {
                    validationError = PageExceptionValidationProvider.validationMsg;
                    invalidPages.add(Long.valueOf(exceptionPage));
                }
            }
        }
        if (!invalidPages.isEmpty()) {
            context.setRefIds(invalidPages);
        }
        return validationError;
    };
    /**
     *  This method returns the total page count from all Content Associations
     *  
     *  @param cas
     *  @return
     */
    prototype.getTotalPageCount = function(cas) {
        var pageCount = 0;
        var ca = cas.iterator();
        var pageGroupIt = null;
        var pageGroup = null;
         while (ca.hasNext()){
            pageGroupIt = ca.next().getPageGroups().iterator();
             while (pageGroupIt.hasNext()){
                pageGroup = pageGroupIt.next();
                pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart() + 1);
            }
        }
        return pageCount;
    };
    /**
     *  Builds the initial allowed page exception list
     *  
     *  @param totalPageCount
     *  @param startPage
     *  @return
     */
    prototype.initialValidPageExceptionList = function(totalPageCount, startPage) {
        var allowedRangeList = new ArrayList();
        var pageNumber = startPage;
         while (pageNumber <= totalPageCount){
            allowedRangeList.add(pageNumber);
            pageNumber = pageNumber + 2;
        }
        allowedRangeList.add(pageNumber);
        return allowedRangeList;
    };
    /**
     *  rebuild the allowed page exception range List after pageException is added
     *  
     *  @param startPage
     *  @param allowedRange
     *  @return
     */
    prototype.rebuildValidPageExceptionList = function(startPage, allowedRange, exceptionPage, totalPageCount, pageExceptionList) {
        var updatedRangeList = new ArrayList();
        var allowedPage = 0;
        var count = 0;
        var paperCount = stjs.trunc(totalPageCount / 2) + pageExceptionList.size();
        if (totalPageCount % 2 != 0) {
            paperCount += 1;
        }
         while (count <= paperCount){
            if (allowedPage == 0) {
                var pageNumber = startPage;
                if ((pageExceptionList.contains(pageNumber)) || pageNumber == exceptionPage) {
                    updatedRangeList.add(pageNumber);
                    allowedPage = pageNumber + 1;
                } else {
                    updatedRangeList.add(pageNumber);
                    allowedPage = pageNumber + 2;
                }
            } else if (pageExceptionList.contains(allowedPage)) {
                updatedRangeList.add(allowedPage);
                allowedPage = allowedPage + 1;
            } else {
                if (allowedPage == exceptionPage) {
                    updatedRangeList.add(allowedPage);
                    allowedPage = allowedPage + 1;
                    updatedRangeList.add(allowedPage);
                    allowedPage = allowedPage + 2;
                } else {
                    updatedRangeList.add(allowedPage);
                    allowedPage = allowedPage + 2;
                }
            }
            count++;
        }
        return updatedRangeList;
    };
    /**
     *  get the start page from the first CA
     *  
     *  @param cas
     *  @return
     */
    prototype.getStartPage = function(cas) {
        var startPage = 0;
        if (cas.size() > 0) {
            var pageGroup = cas.get(0).getPageGroups().get(0);
            startPage = pageGroup.getStart();
        }
        return startPage;
    };
    /**
     *  methods returns validationError if the last page > total pages and if there
     *  are duplicate pages
     *  
     *  @param totalPageCount
     *  @param context
     *  @return
     */
    prototype.validatePagePosition = function(totalPageCount, context) {
        var peInstanceItr = context.getProduct().getPageExceptions().iterator();
        var invalidPages = new ArrayList();
        var pageExceptionsRange = new ArrayList();
         while (peInstanceItr.hasNext()){
            var peInstance = peInstanceItr.next();
            var range = peInstance.getRanges();
            var pageRangeItr = range.iterator();
             while (pageRangeItr.hasNext()){
                var pageRange = pageRangeItr.next();
                var exceptionPage = pageRange.getStart();
                if (pageExceptionsRange.contains(exceptionPage)) {
                    invalidPages.add(Long.valueOf(exceptionPage));
                } else {
                    pageExceptionsRange.add(exceptionPage);
                }
            }
        }
        var allowedPageCount = totalPageCount + pageExceptionsRange.size() + 1;
        peInstanceItr = context.getProduct().getPageExceptions().iterator();
         while (peInstanceItr.hasNext()){
            var peInstance = peInstanceItr.next();
            var range = peInstance.getRanges();
            var pageRangeItr = range.iterator();
             while (pageRangeItr.hasNext()){
                var pageRange = pageRangeItr.next();
                var exceptionPage = pageRange.getStart();
                if (exceptionPage > allowedPageCount) {
                    invalidPages.add(Long.valueOf(exceptionPage));
                }
            }
        }
        var validationError = null;
        if (!invalidPages.isEmpty()) {
            context.setRefIds(invalidPages);
            validationError = PageExceptionValidationProvider.validationMsg;
        }
        return validationError;
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
}, {pageExceptionIdList: {name: "List", arguments: [null]}, type: {name: "Enum", arguments: ["ValueType"]}});

var ContentAssociationProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContentAssociationProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.contentRequirementId = null;
    prototype.getContentRequirementId = function() {
        return this.contentRequirementId;
    };
    prototype.setContentRequirementId = function(contentRequirementId) {
        this.contentRequirementId = contentRequirementId;
    };
    prototype.getFirstContentAssociation = function(context) {
        var ca = null;
        if (this.getContentRequirementId() != null && this.getContentRequirementId() != 0) {
            ca = this.getContentAssociationsForReqId(context.getProduct().getContentAssociations());
            if (ca != null) 
                return ca;
        }
        return ca;
    };
    prototype.getContentAssociationsForReqId = function(cas) {
        var it = cas.iterator();
        var ca = null;
         while (it.hasNext()){
            ca = it.next();
            if (Utils.convertToLongvalue(ca.getContentReqId()) == Utils.convertToLongvalue(this.getContentRequirementId())) {
                return ca;
            }
        }
        return ca;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var TextConcatenationProvider = function() {
    AbstractValueProvider.call(this);
    this.textProviders = new ArrayList();
};
stjs.extend(TextConcatenationProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.textProviders = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var concatText = null;
        ;
        if (this.getTextProviders().size() > 0) {
            var it = this.getTextProviders().iterator();
             while (it.hasNext()){
                var vp = it.next();
                var value = vp.getValueOfType(context, ValueType.STRING);
                if (concatText != null && value != null) 
                    concatText = concatText.concat(value);
                 else 
                    concatText = value;
            }
        }
        return concatText;
    };
    prototype.getTextProviders = function() {
        return this.textProviders;
    };
    prototype.setTextProviders = function(textProviders) {
        this.textProviders = textProviders;
    };
}, {textProviders: {name: "List", arguments: ["ValueProvider"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var FeatureChoiceIdProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(FeatureChoiceIdProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.featureId = null;
    prototype.elementType = null;
    prototype.getFeatureId = function() {
        return this.featureId;
    };
    prototype.setFeatureId = function(featureId) {
        this.featureId = featureId;
    };
    prototype.getElementType = function() {
        return this.elementType;
    };
    prototype.setElementType = function(elementType) {
        this.elementType = elementType;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        if (this.elementType != null && this.elementType == ElementType.PAGEEXCEPTION) {
            return context.getPageException().getFeatureById(this.featureId).getChoice().getId();
        } else {
            return context.getProduct().getFeatureById(this.featureId).getChoice().getId();
        }
    };
    prototype.getType = function() {
        return ValueType.LONG;
    };
}, {elementType: {name: "Enum", arguments: ["ElementType"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var SkuReferenceValueProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(SkuReferenceValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    constructor.HYPHEN = "-";
    prototype.keyProviders = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var key = new String();
        for (var keyProvider in this.keyProviders) {
            key = key + (keyProvider.getValueOfType(context, ValueType.STRING)) + SkuReferenceValueProvider.HYPHEN;
        }
        return (key.substring(0, key.lastIndexOf(SkuReferenceValueProvider.HYPHEN))).toString();
    };
    prototype.getKeyProviders = function() {
        return this.keyProviders;
    };
    prototype.setKeyProviders = function(keyProviders) {
        this.keyProviders = keyProviders;
    };
}, {keyProviders: {name: "List", arguments: ["ValueProvider"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var PropertyValueLengthProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PropertyValueLengthProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.propertyId = null;
    prototype.getPropertyId = function() {
        return this.propertyId;
    };
    prototype.setPropertyId = function(propertyId) {
        this.propertyId = propertyId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var property = null;
        var pit = context.getProduct().getProperties().iterator();
         while (pit.hasNext()){
            property = pit.next();
            if (Utils.convertToLongvalue(property.getId()) == Utils.convertToLongvalue(this.propertyId)) {
                return this.getPropertyLength(property);
            }
        }
        var fit = context.getProduct().getFeatures().iterator();
         while (fit.hasNext()){
            var ci = fit.next().getChoice();
            if (ci != null) {
                pit = ci.getProperties().iterator();
                 while (pit.hasNext()){
                    property = pit.next();
                    if (Utils.convertToLongvalue(property.getId()) == Utils.convertToLongvalue(this.propertyId)) {
                        return this.getPropertyLength(property);
                    }
                }
            }
        }
        return 0;
    };
    prototype.getPropertyLength = function(property) {
        if (property.getValue() != null) {
            return property.getValue().length;
        }
        return 0;
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ProductContextMapKeyProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ProductContextMapKeyProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.key = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        if (context.getValue(this.key) != null) {
            return this.key;
        }
        return "";
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
    prototype.getKey = function() {
        return this.key;
    };
    prototype.setKey = function(key) {
        this.key = key;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

/**
 *  Rule which returns a value of either Boolean, String, Integer, or Decimal based on certain conditions
 *  Contains a list of conditional values
 * 
 *  @author cbochman
 */
var ValueRuleDef = function() {
    this.conditionalValues = new ArrayList();
};
stjs.extend(ValueRuleDef, null, [RuleDefinition], function(constructor, prototype) {
    /**
     * List of conditional values to be evaluated. The value for the first condition which evaluates to true will be returned 
     */
    prototype.conditionalValues = null;
    prototype.operator = null;
    /**
     *  Convenience method for evaluating this rule and converting to a Boolean value
     *  @param context
     *  @return value for the first condition which evaluates to true
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluateBoolean = function(context) {
        var value = this.evaluateType(context, ValueType.BOOLEAN);
        if (value == null) 
            return false;
        return value;
    };
    /**
     *  Convenience method for evaluating this rule and converting to a String value
     *  @param context
     *  @return value for the first condition which evaluates to true
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluateString = function(context) {
        var value = this.evaluateType(context, ValueType.STRING);
        if (value == null) 
            return null;
        return value.toString();
    };
    /**
     *  Convenience method for evaluating this rule and converting to a Decimal value
     *  @param context
     *  @return value for the first condition which evaluates to true
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluateDecimal = function(context) {
        var value = this.evaluateType(context, ValueType.DECIMAL);
        if (value == null) 
            return null;
         else if (stjs.isInstanceOf(value.constructor, BigDecimal)) 
            return value;
        return new BigDecimal(value.toString());
    };
    /**
     *  Convenience method for evaluating this rule and converting to an Integer value
     *  @param context
     *  @return value for the first condition which evaluates to true
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluateInteger = function(context) {
        var value = this.evaluateType(context, ValueType.INTEGER);
        if (value == null) 
            return null;
         else if (stjs.isInstanceOf(value.constructor, Integer)) 
            return value;
        return Utils.convertToInteger(value);
    };
    /**
     *  Evaluate this rule and return a value of the specified type
     *  The value for the first condition which evaluates to true will be returned.
     *  @param context
     *  @param desiredOutputValueType
     *  @return
     *  @throws ConditionException
     *  @throws ValueException
     */
    prototype.evaluateType = function(context, desiredOutputValueType) {
        if (desiredOutputValueType == null) 
            desiredOutputValueType = ValueType.STRING;
        var value = null;
        var cv = null;
        var itConditionalValue = this.conditionalValues.iterator();
         while (itConditionalValue.hasNext()){
            cv = itConditionalValue.next();
            if (cv.getCondition() == null || cv.getCondition().evaluate(context)) {
                if (cv.getValueProvider() != null) {
                    if (this.operator != null && this.operator.equalsIgnoreCase("ALL")) {
                        if (value != null) {
                            value = value.toString().concat(cv.getValueProvider().getValueOfType(context, desiredOutputValueType).toString());
                        } else {
                            value = cv.getValueProvider().getValueOfType(context, desiredOutputValueType);
                        }
                    } else {
                        value = cv.getValueProvider().getValueOfType(context, desiredOutputValueType);
                        break;
                    }
                } else {
                    break;
                }
            }
        }
        return value;
    };
    prototype.evaluate = function(context) {
        return this.evaluateType(context, null);
    };
    prototype.getConditionalValues = function() {
        return this.conditionalValues;
    };
    prototype.setConditionalValues = function(conditionalValues) {
        this.conditionalValues = conditionalValues;
    };
    prototype.getOperator = function() {
        return this.operator;
    };
    prototype.setOperator = function(operator) {
        this.operator = operator;
    };
}, {conditionalValues: {name: "List", arguments: ["ConditionalValue"]}});

var DynamicValidationResultProvider = function() {
    AbstractValidationResultProvider.call(this);
};
stjs.extend(DynamicValidationResultProvider, AbstractValidationResultProvider, [], function(constructor, prototype) {
    prototype.desc = null;
    prototype.reason = null;
    prototype.getResult = function(context) {
        var result = new ValidationResult();
        if (this.desc != null) 
            result.setDesc(this.desc.getValueOfType(context, ValueType.STRING));
        result.setName(this.getName());
        result.setRefIds(this.getRefIds());
        result.setSeverity(this.getSeverity());
        return result;
    };
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.getReason = function() {
        return this.reason;
    };
    prototype.setReason = function(reason) {
        this.reason = reason;
    };
}, {desc: "ValueProvider", reason: "ValueProvider", severity: {name: "Enum", arguments: ["ValidationSeverity"]}, type: {name: "Enum", arguments: ["ValidationType"]}, refIds: {name: "List", arguments: [null]}});

var StaticValidationResultProvider = function() {
    AbstractValidationResultProvider.call(this);
};
stjs.extend(StaticValidationResultProvider, AbstractValidationResultProvider, [], function(constructor, prototype) {
    prototype.desc = null;
    prototype.reason = null;
    prototype.code = null;
    prototype.getCode = function() {
        return this.code;
    };
    prototype.setCode = function(code) {
        this.code = code;
    };
    prototype.getResult = function(context) {
        var result = new ValidationResult();
        result.setDesc(ValidationResultMappings.getValidationDescByCode(ValidationResultCode.valueOf(this.code)));
        result.setName(this.getName());
        var refIds = context.getRefIds().isEmpty() ? this.getRefIds() : context.getRefIds();
        result.setRefIds(refIds);
        result.setCode(this.getCode());
        result.setSeverity(ValidationResultMappings.getValidationSeverityByCode(ValidationResultCode.valueOf(this.code)));
        return result;
    };
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.getReason = function() {
        return this.reason;
    };
    prototype.setReason = function(reason) {
        this.reason = reason;
    };
}, {severity: {name: "Enum", arguments: ["ValidationSeverity"]}, type: {name: "Enum", arguments: ["ValidationType"]}, refIds: {name: "List", arguments: [null]}});

var IdListCondition = function() {
    AbstractCondition.call(this);
};
stjs.extend(IdListCondition, AbstractCondition, [], function(constructor, prototype) {
    prototype.idList = null;
    prototype.getIdList = function() {
        return this.idList;
    };
    prototype.setIdList = function(idList) {
        this.idList = idList;
    };
}, {idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var PageException = function() {
    AbstractPageException.call(this);
    this.contentReqRefIds = new ArraySet();
    this.featureRefs = new ArrayList();
};
stjs.extend(PageException, AbstractPageException, [], function(constructor, prototype) {
    prototype.required = false;
    prototype.contentReqRefIds = null;
    prototype.featureRefs = null;
    prototype.isRequired = function() {
        return this.required;
    };
    prototype.setRequired = function(required) {
        this.required = required;
    };
    prototype.getContentReqRefIds = function() {
        return this.contentReqRefIds;
    };
    prototype.setContentReqRefIds = function(contentReqRefIds) {
        this.contentReqRefIds = contentReqRefIds;
    };
    prototype.getFeatureRefs = function() {
        return this.featureRefs;
    };
    prototype.setFeatureRefs = function(featureRefs) {
        this.featureRefs = featureRefs;
    };
    prototype.setFeatures = function(features) {
        AbstractPageException.prototype.setFeatures.call(this, features);
    };
    prototype.setProperties = function(properties) {
        AbstractPageException.prototype.setProperties.call(this, properties);
    };
}, {contentReqRefIds: {name: "Set", arguments: [null]}, featureRefs: {name: "List", arguments: ["FeatureReference"]}, features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}});

var PropertyInstance = function() {
    AbstractProperty.call(this);
};
stjs.extend(PropertyInstance, AbstractProperty, [], null, {});

var Feature = function() {
    AbstractFeature.call(this);
    this.choices = new ArraySet();
};
stjs.extend(Feature, AbstractFeature, [], function(constructor, prototype) {
    prototype.defaultChoiceId = null;
    prototype.choiceRequired = false;
    prototype.overrideWithDefault = false;
    prototype.choices = null;
    prototype.getChoiceById = function(id) {
        var choice = Utils.getElementById(id, this.getChoices());
        return choice;
    };
    prototype.getDefaultChoiceId = function() {
        return this.defaultChoiceId;
    };
    prototype.setDefaultChoiceId = function(defaultChoiceId) {
        this.defaultChoiceId = defaultChoiceId;
    };
    prototype.isChoiceRequired = function() {
        return this.choiceRequired;
    };
    prototype.setChoiceRequired = function(choiceRequired) {
        this.choiceRequired = choiceRequired;
    };
    prototype.isOverrideWithDefault = function() {
        return this.overrideWithDefault;
    };
    prototype.setOverrideWithDefault = function(overrideWithDefault) {
        this.overrideWithDefault = overrideWithDefault;
    };
    prototype.getChoices = function() {
        return this.choices;
    };
    prototype.setChoices = function(choices) {
        this.choices = choices;
    };
}, {choices: {name: "Set", arguments: ["C"]}});

var DesignTemplate = function() {
    this.featureRefs = new ArrayList();
    this.templates = new ArrayList();
};
stjs.extend(DesignTemplate, null, [], function(constructor, prototype) {
    prototype.vendorProductId = null;
    prototype.vendorProductVersion = null;
    prototype.vendorCode = null;
    prototype.featureRefs = null;
    prototype.templates = null;
    prototype.getVendorCode = function() {
        return this.vendorCode;
    };
    prototype.setVendorCode = function(vendorCode) {
        this.vendorCode = vendorCode;
    };
    prototype.getFeatureRefs = function() {
        return this.featureRefs;
    };
    prototype.setFeatureRefs = function(featureRefs) {
        this.featureRefs = featureRefs;
    };
    prototype.getTemplates = function() {
        return this.templates;
    };
    prototype.setTemplates = function(templates) {
        this.templates = templates;
    };
    prototype.getVendorProductId = function() {
        return this.vendorProductId;
    };
    prototype.setVendorProductId = function(vendorProductId) {
        this.vendorProductId = vendorProductId;
    };
    prototype.getVendorProductVersion = function() {
        return this.vendorProductVersion;
    };
    prototype.setVendorProductVersion = function(vendorProductVersion) {
        this.vendorProductVersion = vendorProductVersion;
    };
}, {vendorCode: {name: "Enum", arguments: ["DesignVendorCode"]}, featureRefs: {name: "List", arguments: ["FeatureReference"]}, templates: {name: "List", arguments: ["Template"]}});

var ChoiceInstance = function() {
    AbstractChoice.call(this);
};
stjs.extend(ChoiceInstance, AbstractChoice, [], function(constructor, prototype) {
    prototype.setProperties = function(properties) {
        AbstractChoice.prototype.setProperties.call(this, properties);
    };
}, {properties: {name: "Set", arguments: ["P"]}});

var Choice = function() {
    AbstractChoice.call(this);
    this.compatibilityGroups = new ArraySet();
};
stjs.extend(Choice, AbstractChoice, [], function(constructor, prototype) {
    prototype.compatibilityGroups = null;
    prototype.getCompatibilityGroups = function() {
        return this.compatibilityGroups;
    };
    prototype.setCompatibilityGroups = function(compatibilityGroups) {
        this.compatibilityGroups = compatibilityGroups;
    };
    prototype.setProperties = function(properties) {
        AbstractChoice.prototype.setProperties.call(this, properties);
    };
}, {compatibilityGroups: {name: "Set", arguments: ["CompatibilityGroup"]}, properties: {name: "Set", arguments: ["P"]}});

var ContentAssociation = function() {
    this.pageGroups = new ArrayList();
};
stjs.extend(ContentAssociation, null, [], function(constructor, prototype) {
    prototype.parentContentReference = null;
    prototype.contentReference = null;
    prototype.contentReplacementUrl = null;
    prototype.contentType = null;
    prototype.fileSizeBytes = 0;
    prototype.fileName = null;
    prototype.printReady = false;
    prototype.contentReqId = null;
    prototype.name = null;
    prototype.desc = null;
    prototype.purpose = null;
    prototype.specialInstructions = null;
    prototype.pageGroups = null;
    prototype.physicalContent = false;
    prototype.fileSource = null;
    prototype.fileSequenceId = null;
    prototype.asyncFileSource = null;
    prototype.getParentContentReference = function() {
        return this.parentContentReference;
    };
    prototype.setParentContentReference = function(parentContentReference) {
        this.parentContentReference = parentContentReference;
    };
    prototype.getContentReference = function() {
        return this.contentReference;
    };
    prototype.setContentReference = function(contentReference) {
        this.contentReference = contentReference;
    };
    prototype.getContentReplacementUrl = function() {
        return this.contentReplacementUrl;
    };
    prototype.setContentReplacementUrl = function(contentReplacementUrl) {
        this.contentReplacementUrl = contentReplacementUrl;
    };
    prototype.getContentType = function() {
        return this.contentType;
    };
    prototype.setContentType = function(contentType) {
        this.contentType = contentType;
    };
    prototype.getFileSizeBytes = function() {
        return this.fileSizeBytes;
    };
    prototype.setFileSizeBytes = function(fileSizeBytes) {
        this.fileSizeBytes = fileSizeBytes;
    };
    prototype.getFileName = function() {
        return this.fileName;
    };
    prototype.setFileName = function(fileName) {
        this.fileName = fileName;
    };
    prototype.isPrintReady = function() {
        return this.printReady;
    };
    prototype.setPrintReady = function(printReady) {
        this.printReady = printReady;
    };
    prototype.getContentReqId = function() {
        return this.contentReqId;
    };
    prototype.setContentReqId = function(contentReqId) {
        this.contentReqId = contentReqId;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.getPurpose = function() {
        return this.purpose;
    };
    prototype.setPurpose = function(purpose) {
        this.purpose = purpose;
    };
    prototype.getSpecialInstructions = function() {
        return this.specialInstructions;
    };
    prototype.setSpecialInstructions = function(specialInstructions) {
        this.specialInstructions = specialInstructions;
    };
    prototype.getPageGroups = function() {
        return this.pageGroups;
    };
    prototype.setPageGroups = function(pageGroups) {
        this.pageGroups = pageGroups;
    };
    prototype.isPhysicalContent = function() {
        return this.physicalContent;
    };
    prototype.setPhysicalContent = function(physicalContent) {
        this.physicalContent = physicalContent;
    };
    prototype.getFileSource = function() {
        return this.fileSource;
    };
    prototype.setFileSource = function(fileSource) {
        this.fileSource = fileSource;
    };
    prototype.getFileSequenceId = function() {
        return this.fileSequenceId;
    };
    prototype.setFileSequenceId = function(fileSequenceId) {
        this.fileSequenceId = fileSequenceId;
    };
    prototype.getAsyncFileSource = function() {
        return this.asyncFileSource;
    };
    prototype.setAsyncFileSource = function(asyncFileSource) {
        this.asyncFileSource = asyncFileSource;
    };
}, {pageGroups: {name: "List", arguments: ["PageGroup"]}});

var ContentValidationResult = function() {
    ValidationResult.call(this);
};
stjs.extend(ContentValidationResult, ValidationResult, [], function(constructor, prototype) {
    prototype.purpose = null;
    prototype.parentContentReference = null;
    prototype.contentReference = null;
    prototype.pageGroups = null;
    prototype.getPurpose = function() {
        return this.purpose;
    };
    prototype.setPurpose = function(purpose) {
        this.purpose = purpose;
    };
    prototype.getParentContentReference = function() {
        return this.parentContentReference;
    };
    prototype.setParentContentReference = function(parentContentReference) {
        this.parentContentReference = parentContentReference;
    };
    prototype.getContentReference = function() {
        return this.contentReference;
    };
    prototype.setContentReference = function(contentReference) {
        this.contentReference = contentReference;
    };
    prototype.getPageGroups = function() {
        return this.pageGroups;
    };
    prototype.setPageGroups = function(pageGroups) {
        this.pageGroups = pageGroups;
    };
}, {pageGroups: {name: "List", arguments: ["PageGroup"]}, severity: {name: "Enum", arguments: ["ValidationSeverity"]}, refIds: {name: "List", arguments: [null]}, elementType: {name: "Enum", arguments: ["ElementType"]}});

/**
 *  Represent Print Product Associations for integrating with Imposition Service via OrderPerpService
 *  
 *  @author 5010701
 *  @author Naga Vankayalapati
 */
var ProductionContentAssociation = function() {};
stjs.extend(ProductionContentAssociation, null, [], function(constructor, prototype) {
    prototype.parentContentReference = null;
    prototype.contentReference = null;
    prototype.contentState = null;
    prototype.contentType = null;
    prototype.purpose = null;
    prototype.fileName = null;
    prototype.contentUrl = null;
    prototype.pageGroups = null;
    prototype.getParentContentReference = function() {
        return this.parentContentReference;
    };
    prototype.setParentContentReference = function(parentContentReference) {
        this.parentContentReference = parentContentReference;
    };
    prototype.getContentReference = function() {
        return this.contentReference;
    };
    prototype.setContentReference = function(contentReference) {
        this.contentReference = contentReference;
    };
    prototype.getContentState = function() {
        return this.contentState;
    };
    prototype.setContentState = function(contentState) {
        this.contentState = contentState;
    };
    prototype.getContentType = function() {
        return this.contentType;
    };
    prototype.setContentType = function(contentType) {
        this.contentType = contentType;
    };
    prototype.getPurpose = function() {
        return this.purpose;
    };
    prototype.setPurpose = function(purpose) {
        this.purpose = purpose;
    };
    prototype.getFileName = function() {
        return this.fileName;
    };
    prototype.setFileName = function(fileName) {
        this.fileName = fileName;
    };
    prototype.getContentUrl = function() {
        return this.contentUrl;
    };
    prototype.setContentUrl = function(contentUrl) {
        this.contentUrl = contentUrl;
    };
    prototype.getPageGroups = function() {
        return this.pageGroups;
    };
    prototype.setPageGroups = function(pageGroups) {
        this.pageGroups = pageGroups;
    };
}, {contentState: {name: "Enum", arguments: ["ContentState"]}, contentType: {name: "Enum", arguments: ["ContentType"]}, purpose: {name: "Enum", arguments: ["ContentPurpose"]}, pageGroups: {name: "List", arguments: ["PageGroup"]}});

var Property = function() {
    AbstractProperty.call(this);
    this.bounds = new ArraySet();
};
stjs.extend(Property, AbstractProperty, [], function(constructor, prototype) {
    prototype.required = false;
    prototype.bounds = null;
    prototype.bound = null;
    prototype.inputAllowed = false;
    prototype.clone = function() {
        var c = new Property();
        c.setId(this.getId());
        c.setName(this.getName());
        c.setRequired(this.required);
        c.setInputAllowed(this.inputAllowed);
        c.setBound(this.bound);
        c.setBounds(this.bounds);
        return c;
    };
    prototype.isRequired = function() {
        return this.required;
    };
    prototype.setRequired = function(required) {
        this.required = required;
    };
    prototype.isInputAllowed = function() {
        return this.inputAllowed;
    };
    prototype.setInputAllowed = function(inputAllowed) {
        this.inputAllowed = inputAllowed;
    };
    prototype.getBound = function() {
        return this.bound;
    };
    prototype.setBound = function(bound) {
        this.bound = bound;
    };
    prototype.getBounds = function() {
        return this.bounds;
    };
    prototype.setBounds = function(bounds) {
        this.bounds = bounds;
    };
}, {bounds: {name: "Set", arguments: ["Bound"]}, bound: "Bound"});

var ContentRequirement = function() {
    ProductElement.call(this);
    this.allowedSizes = new ArrayList();
};
stjs.extend(ContentRequirement, ProductElement, [], function(constructor, prototype) {
    prototype.name = null;
    prototype.desc = null;
    prototype.purpose = null;
    prototype.contentGroup = null;
    prototype.requiresPrintReady = false;
    prototype.allowMixedOrientation = false;
    prototype.allowMixedSize = false;
    prototype.resizeIfDefault = false;
    prototype.minPages = 0;
    prototype.maxPages = 0;
    prototype.maxFiles = 0;
    prototype.allowedSizes = null;
    prototype.contentReferences = null;
    prototype.bleedDimension = null;
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getDesc = function() {
        return this.desc;
    };
    prototype.setDesc = function(desc) {
        this.desc = desc;
    };
    prototype.getPurpose = function() {
        return this.purpose;
    };
    prototype.setPurpose = function(purpose) {
        this.purpose = purpose;
    };
    prototype.getContentGroup = function() {
        return this.contentGroup;
    };
    prototype.setContentGroup = function(contentGroup) {
        this.contentGroup = contentGroup;
    };
    prototype.isAllowMixedOrientation = function() {
        return this.allowMixedOrientation;
    };
    prototype.setAllowMixedOrientation = function(allowMixedOrientation) {
        this.allowMixedOrientation = allowMixedOrientation;
    };
    prototype.isAllowMixedSize = function() {
        return this.allowMixedSize;
    };
    prototype.setAllowMixedSize = function(allowMixedSize) {
        this.allowMixedSize = allowMixedSize;
    };
    prototype.isResizeIfDefault = function() {
        return this.resizeIfDefault;
    };
    prototype.setResizeIfDefault = function(resizeIfDefault) {
        this.resizeIfDefault = resizeIfDefault;
    };
    prototype.getMinPages = function() {
        return this.minPages;
    };
    prototype.setMinPages = function(minPages) {
        this.minPages = minPages;
    };
    prototype.getMaxPages = function() {
        return this.maxPages;
    };
    prototype.setMaxPages = function(maxPages) {
        this.maxPages = maxPages;
    };
    prototype.getAllowedSizes = function() {
        return this.allowedSizes;
    };
    prototype.setAllowedSizes = function(allowedSizes) {
        this.allowedSizes = allowedSizes;
    };
    prototype.isRequiresPrintReady = function() {
        return this.requiresPrintReady;
    };
    prototype.setRequiresPrintReady = function(requiresPrintReady) {
        this.requiresPrintReady = requiresPrintReady;
    };
    prototype.getMaxFiles = function() {
        return this.maxFiles;
    };
    prototype.setMaxFiles = function(maxFiles) {
        this.maxFiles = maxFiles;
    };
    prototype.getContentReferences = function() {
        return this.contentReferences;
    };
    prototype.setContentReferences = function(contentReferences) {
        this.contentReferences = contentReferences;
    };
    prototype.getBleedDimension = function() {
        return this.bleedDimension;
    };
    prototype.setBleedDimension = function(bleedDimension) {
        this.bleedDimension = bleedDimension;
    };
    prototype.clone = function() {
        var c = new ContentRequirement();
        c.setId(this.getId());
        c.setName(this.name);
        c.setDesc(this.desc);
        c.setPurpose(this.purpose);
        c.setContentGroup(this.contentGroup);
        c.setRequiresPrintReady(this.requiresPrintReady);
        c.setAllowMixedOrientation(this.allowMixedOrientation);
        c.setAllowMixedSize(this.allowMixedSize);
        c.setResizeIfDefault(this.resizeIfDefault);
        c.setMinPages(this.minPages);
        c.setMaxPages(this.maxPages);
        c.setMaxFiles(this.maxFiles);
        c.setAllowedSizes(this.allowedSizes);
        c.setContentReferences(this.contentReferences);
        c.setBleedDimension(this.bleedDimension);
        return c;
    };
}, {allowedSizes: {name: "List", arguments: ["ContentDimensions"]}, contentReferences: {name: "List", arguments: [null]}, bleedDimension: "BleedDimension"});

var PropertyDisplay = function() {
    ElementDisplay.call(this);
    this.allowedValues = new ArrayList();
};
stjs.extend(PropertyDisplay, ElementDisplay, [], function(constructor, prototype) {
    prototype.allowedValues = null;
    prototype.getAllowedValues = function() {
        return this.allowedValues;
    };
    prototype.setAllowedValues = function(allowedValues) {
        this.allowedValues = allowedValues;
    };
}, {allowedValues: {name: "List", arguments: ["PropertyInputDetailsValue"]}, controlIds: {name: "List", arguments: [null]}, displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}});

var EntryDisplayDetails = function() {
    DisplayDetails.call(this);
};
stjs.extend(EntryDisplayDetails, DisplayDetails, [], function(constructor, prototype) {
    prototype.version = null;
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
}, {displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var PropertyInputDetails = function() {
    DisplayDetails.call(this);
};
stjs.extend(PropertyInputDetails, DisplayDetails, [], function(constructor, prototype) {
    prototype.allowedValues = null;
    prototype.getAllowedValues = function() {
        return this.allowedValues;
    };
    prototype.setAllowedValues = function(allowedValues) {
        this.allowedValues = allowedValues;
    };
}, {allowedValues: {name: "List", arguments: ["PropertyInputDetailsValue"]}, displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var SkuDisplayDetails = function() {
    DisplayDetails.call(this);
};
stjs.extend(SkuDisplayDetails, DisplayDetails, [], null, {displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var ProductDisplayDetails = function() {
    DisplayDetails.call(this);
};
stjs.extend(ProductDisplayDetails, DisplayDetails, [], null, {displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var ValueDisplayDetails = function() {
    DisplayDetails.call(this);
};
stjs.extend(ValueDisplayDetails, DisplayDetails, [], function(constructor, prototype) {
    prototype.valueType = null;
    prototype.getValueType = function() {
        return this.valueType;
    };
    prototype.setValueType = function(valueType) {
        this.valueType = valueType;
    };
}, {valueType: {name: "Enum", arguments: ["DisplayValueType"]}, displayHints: {name: "List", arguments: ["DisplayHint"]}, displayTexts: {name: "List", arguments: ["DisplayText"]}, controlIds: {name: "List", arguments: [null]}, displays: {name: "List", arguments: ["D"]}});

var ProductRules = function() {
    this.refIdRuleMap = new ArrayMap();
    this.rules = new ArrayList();
};
stjs.extend(ProductRules, null, [], function(constructor, prototype) {
    prototype.rules = null;
    prototype.refIdRuleMap = null;
    prototype.addRules = function(rules) {
        var itRules = rules.iterator();
        var rule = null;
         while (itRules.hasNext()){
            rule = itRules.next();
            this.addRule(rule);
        }
    };
    prototype.addRule = function(rule) {
        if (rule.getRefId() == null) 
             throw new ProductConfigProcessorException("refId cannot be null for rule", null);
        var refRules = this.getRulesByRefId(rule.getRefId());
        if (refRules == null) {
            refRules = new ArrayList();
            this.refIdRuleMap.put(rule.getRefId(), refRules);
        }
        refRules.add(rule);
        this.rules.add(rule);
    };
    prototype.getRulesByRefId = function(refId) {
        if (this.refIdRuleMap.containsKey(refId)) 
            return this.refIdRuleMap.get(refId);
        return null;
    };
    prototype.getRulesByRefIdAndType = function(refId, type) {
        var foundRules = new ArrayList();
        var refRules = this.getRulesByRefId(refId);
        if (refRules != null) {
            var itRules = refRules.iterator();
            var rule = null;
             while (itRules.hasNext()){
                rule = itRules.next();
                if (Utils.isStringsEqual(rule.getType().getId(), type.getId())) 
                    foundRules.add(rule);
            }
        }
        return foundRules;
    };
    prototype.getSingleRuleDef = function(refId, type) {
        var rule = this.getSingleRule(refId, type);
        if (rule != null) 
            return rule.getDef();
        return null;
    };
    prototype.getSingleRule = function(refId, type) {
        var rule = null;
        var rules = this.getRulesByRefIdAndType(refId, type);
        if (rules.size() > 1) {
             throw new RuleConfigurationException("Only 1 (" + type.getDesc() + ") rule allowed per entity. Found (" + rules.size() + ") for refId: " + refId, null);
        }
        if (rules.size() == 1) {
            rule = rules.iterator().next();
        }
        return rule;
    };
    prototype.getRules = function() {
        return this.rules;
    };
    prototype.setRules = function(rules) {
        this.rules = rules;
        var refRules = null;
        if (!(rules.isEmpty())) {
            var itRules = rules.iterator();
            var rule = null;
             while (itRules.hasNext()){
                rule = itRules.next();
                refRules = this.getRulesByRefId(rule.getRefId());
                if (refRules == null) {
                    refRules = new ArrayList();
                }
                refRules.add(rule);
                this.refIdRuleMap.put(rule.getRefId(), refRules);
            }
        }
    };
}, {rules: {name: "List", arguments: ["Rule"]}, refIdRuleMap: {name: "Map", arguments: [null, {name: "List", arguments: ["Rule"]}]}});

var ContextKeysCondition = function() {
    AbstractCondition.call(this);
};
stjs.extend(ContextKeysCondition, AbstractCondition, [], function(constructor, prototype) {
    prototype.contextKeys = null;
    prototype.getContextKeys = function() {
        return this.contextKeys;
    };
    prototype.setContextKeys = function(contextKeys) {
        this.contextKeys = contextKeys;
    };
    prototype.evaluate = function(context) {
        switch (this.getBooleanOperator()) {
            case BooleanOperator.NON_EMPTY:
                return context.getProduct() != null && context.getProduct().getContextKeys() != null && !context.getProduct().getContextKeys().isEmpty();
            case BooleanOperator.AND:
                return context.getProduct() != null && this.containsAll(context.getProduct().getContextKeys());
            case BooleanOperator.OR:
                return context.getProduct() != null && this.containsAny(context.getProduct().getContextKeys());
            case BooleanOperator.NOT:
                return context.getProduct() == null || this.containsNone(context.getProduct().getContextKeys());
            default:
                 throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
        }
    };
    prototype.containsAny = function(productContextKeys) {
        var contextKey = null;
        if (productContextKeys == null || productContextKeys.isEmpty()) {
            return false;
        }
        var it = this.getContextKeys().iterator();
         while (it.hasNext()){
            contextKey = it.next();
            if (productContextKeys.contains(contextKey)) 
                return true;
        }
        return false;
    };
    prototype.containsAll = function(productContextKeys) {
        if (productContextKeys == null || productContextKeys.isEmpty()) {
            return false;
        }
        var contextKey = null;
        var it = this.getContextKeys().iterator();
         while (it.hasNext()){
            contextKey = it.next();
            if (!productContextKeys.contains(contextKey)) 
                return false;
        }
        return true;
    };
    prototype.containsNone = function(productContextKeys) {
        var contextKey = null;
        var it = this.getContextKeys().iterator();
         while (it.hasNext()){
            contextKey = it.next();
            if (productContextKeys.contains(contextKey)) 
                return false;
        }
        return true;
    };
}, {contextKeys: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

/**
 *  Used for grouping multiple conditions together based on AND, OR, NOT (BooleanOperator)
 *  @author cbochman
 */
var GroupCondition = function() {
    AbstractCondition.call(this);
    this.conditions = new ArrayList();
};
stjs.extend(GroupCondition, AbstractCondition, [], function(constructor, prototype) {
    prototype.conditions = null;
    prototype.evaluate = function(context) {
        if (this.getBooleanOperator() == null) 
             throw new ConditionException("BooleanOperator must be set before executing this condition", null);
        if (this.getConditions().size() > 0) {
            var it = this.getConditions().iterator();
            switch (this.getBooleanOperator()) {
                case BooleanOperator.AND:
                     while (it.hasNext()){
                        if (!it.next().evaluate(context)) 
                            return false;
                    }
                    return true;
                case BooleanOperator.OR:
                     while (it.hasNext()){
                        if (it.next().evaluate(context)) 
                            return true;
                    }
                    return false;
                case BooleanOperator.NOT:
                     while (it.hasNext()){
                        if (it.next().evaluate(context)) 
                            return false;
                    }
                    return true;
                default:
                     throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
            }
        }
        return false;
    };
    prototype.getConditions = function() {
        return this.conditions;
    };
    prototype.setConditions = function(conditions) {
        this.conditions = conditions;
    };
}, {conditions: {name: "List", arguments: ["Condition"]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var PageExceptionTotalPageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageExceptionTotalPageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.pageExceptionId = null;
    prototype.getPageExceptionId = function() {
        return this.pageExceptionId;
    };
    prototype.setPageExceptionId = function(pageExceptionId) {
        this.pageExceptionId = pageExceptionId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var pageExceptions = context.getProduct().getPageExceptions();
        var it = pageExceptions.iterator();
        var pageRangeIt = null;
        var pageExceptionInstance = null;
        var pageRange = null;
        var pageCount = 0;
         while (it.hasNext()){
            pageExceptionInstance = it.next();
            if (this.pageExceptionId.equals(pageExceptionInstance.getId())) {
                pageRangeIt = pageExceptionInstance.getRanges().iterator();
                 while (pageRangeIt.hasNext()){
                    pageRange = pageRangeIt.next();
                    pageCount = pageCount + (pageRange.getEnd() - pageRange.getStart() + 1);
                }
            }
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, this.getType(), desiredOutputValueType);
        }
        return pageCount;
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ProductIdValueProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ProductIdValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(context.getProduct().getId(), this.getType(), desiredOutputValueType);
        }
        return context.getProduct().getId();
    };
    prototype.getType = function() {
        return ValueType.LONG;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var SingleSidePageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(SingleSidePageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.elementType = null;
    prototype.getElementType = function() {
        return this.elementType;
    };
    prototype.setElementType = function(elementType) {
        this.elementType = elementType;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var productPageRanges = null;
        productPageRanges = PageRangeProcessor.splitpage(context, this.elementType);
        var isSingleSide = false;
        var isPrintOrInsertCover = false;
        var isFirstPageColor = false;
        var fis = null;
        if (this.elementType != null && this.elementType == ElementType.PAGEEXCEPTION) {
            fis = context.getPageException().getFeatures();
        } else {
            fis = context.getProduct().getFeatures();
        }
        for (var fi in fis) {
            for (var prpti in fi.getChoice().getProperties()) {
                if (("PRINT_FIRST_PAGE_COVER".equals(prpti.getName()) || "FIRST_PAGE_ON_CLEAR_BINDER".equals(prpti.getName())) && "YES".equalsIgnoreCase(prpti.getValue())) {
                    isPrintOrInsertCover = true;
                } else if ("PRINT_COLOR".equals(prpti.getName()) && "FIRST_COLOR_REMINAING_BLACK".equalsIgnoreCase(prpti.getValue())) {
                    isFirstPageColor = true;
                } else if ("SIDE".equals(prpti.getName()) && "SINGLE".equalsIgnoreCase(prpti.getValue())) {
                    isSingleSide = true;
                }
            }
        }
        var pageCount = 0;
        var pgItr = productPageRanges.iterator();
         while (pgItr.hasNext()){
            var range = pgItr.next();
            pageCount = pageCount + this.getPageCount(range, isSingleSide, isPrintOrInsertCover, isFirstPageColor);
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, desiredOutputValueType);
        } else {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, this.getType());
        }
    };
    prototype.getPageCount = function(range, isSingleSide, isPrintOrInsertCover, isFirstPageColor) {
        var pageCount = range.getEnd() - range.getStart() + 1;
        if (isSingleSide) {
            if ((range.getStart() == 1) && (isFirstPageColor || isPrintOrInsertCover)) {
                pageCount = pageCount - 1;
            }
        } else {
            if (pageCount % 2 == 0) {
                if ((range.getStart() == 1) && (isPrintOrInsertCover)) {
                    pageCount = 1;
                } else {
                    pageCount = 0;
                }
            } else {
                if (pageCount > 1) {
                    if ((range.getStart() == 1) && isPrintOrInsertCover) {
                        pageCount = 0;
                    } else if ((range.getStart() == 1) && isFirstPageColor) {
                        pageCount = 1;
                    } else {
                        pageCount = 1;
                    }
                } else {
                    if ((range.getStart() == 1) && (isFirstPageColor || isPrintOrInsertCover)) {
                        pageCount = 0;
                    } else {
                        pageCount = 1;
                    }
                }
            }
        }
        return pageCount;
    };
}, {elementType: {name: "Enum", arguments: ["ElementType"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var CalcValueProvider = function() {
    AbstractValueProvider.call(this);
    this.op = CalcOperator.ADD;
};
stjs.extend(CalcValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.op = null;
    prototype.val1 = null;
    prototype.val2 = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var value = null;
        if (this.val1 != null && this.val2 != null) {
            var type = ValueConverter.getHighestType(this.val1.getType(), this.val2.getType());
            switch (type) {
                case ValueType.DECIMAL:
                    value = this.calcDecimals(context, desiredOutputValueType);
                    break;
                case ValueType.INTEGER:
                    value = this.calcIntegers(context, desiredOutputValueType);
                    break;
                case ValueType.STRING:
                    value = this.calcStrings(context, desiredOutputValueType);
                    break;
                default:
                     throw new ValueException("Calculation not supported on value type: " + type, null);
            }
        }
        return value;
    };
    prototype.calcDecimals = function(context, desiredOutputValueType) {
        var value = null;
        var v1 = this.val1.getValueOfType(context, ValueType.DECIMAL);
        var v2 = this.val2.getValueOfType(context, ValueType.DECIMAL);
        switch (this.op) {
            case CalcOperator.ADD:
                value = v1.add(v2);
                break;
            case CalcOperator.SUB:
                value = v1.subtract(v2);
                break;
            case CalcOperator.MULT:
                value = v1.multiply(v2);
                break;
            case CalcOperator.DIV:
                value = v1.divide(v2);
                break;
            default:
                 throw new ValueException(this.op + " not yet supported on Decimals", null);
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.DECIMAL, desiredOutputValueType);
        return null;
    };
    prototype.calcIntegers = function(context, desiredOutputValueType) {
        var value = null;
        var v1 = this.val1.getValueOfType(context, ValueType.INTEGER);
        var v2 = this.val2.getValueOfType(context, ValueType.INTEGER);
        switch (this.op) {
            case CalcOperator.ADD:
                value = v1 + v2;
                break;
            case CalcOperator.SUB:
                value = v1 - v2;
                break;
            case CalcOperator.MULT:
                value = v1 * v2;
                break;
            case CalcOperator.DIV:
                value = v1 / v2;
                break;
            case CalcOperator.DIV_ROUND_UP:
                value = (v1 + v2 - 1) / v2;
                break;
            case CalcOperator.ROUND_UP_TO_MULTIPLE:
                value = (v1 + v2 - 1) / v2 * v2;
                break;
            case CalcOperator.MOD:
                value = v1 % v2;
                break;
            default:
                 throw new ValueException(this.op + " not yet supported on Integers", null);
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.INTEGER, desiredOutputValueType);
        return null;
    };
    prototype.calcStrings = function(context, desiredOutputValueType) {
        var value = null;
        var v1 = this.val1.getValueOfType(context, ValueType.STRING);
        var v2 = this.val2.getValueOfType(context, ValueType.STRING);
        switch (this.op) {
            case CalcOperator.ADD:
                value = v1 + v2;
                break;
            default:
                 throw new ValueException(this.op + " not supported with String types", null);
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.STRING, desiredOutputValueType);
        return null;
    };
    prototype.getOp = function() {
        return this.op;
    };
    prototype.setOp = function(operator) {
        this.op = operator;
    };
    prototype.getVal1 = function() {
        return this.val1;
    };
    prototype.setVal1 = function(valueProvider1) {
        this.val1 = valueProvider1;
    };
    prototype.getVal2 = function() {
        return this.val2;
    };
    prototype.setVal2 = function(valueProvider2) {
        this.val2 = valueProvider2;
    };
}, {op: {name: "Enum", arguments: ["CalcOperator"]}, val1: "ValueProvider", val2: "ValueProvider", type: {name: "Enum", arguments: ["ValueType"]}});

var StaticValueProvider = function() {
    AbstractValueProvider.call(this);
    this.value = null;
};
stjs.extend(StaticValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.value = null;
    prototype.getValueOfType = function(context, requestedOutputValueType) {
        return ValueConverter.convertToType(this.value, this.getType(), requestedOutputValueType);
    };
    prototype.getValue = function() {
        return this.value;
    };
    prototype.setValue = function(value) {
        this.value = value;
    };
}, {value: "Object", type: {name: "Enum", arguments: ["ValueType"]}});

var GrommetTopEdgeCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(GrommetTopEdgeCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var count = 2;
        var height = 0;
        var width = 0;
        var orientation = null;
        var featureSet = context.getProduct().getFeatures();
        var fiIt = featureSet.iterator();
         while (fiIt.hasNext()){
            var fi = fiIt.next();
            var ch = fi.getChoice();
            var propertiesSet = ch.getProperties();
            var propertyIt = propertiesSet.iterator();
             while (propertyIt.hasNext()){
                var property = propertyIt.next();
                if (Utils.isStringsEqual(property.getName(), "MEDIA_HEIGHT")) {
                    height = Utils.convertToFloat(property.getValue());
                } else if (Utils.isStringsEqual(property.getName(), "MEDIA_WIDTH")) {
                    width = Utils.convertToFloat(property.getValue());
                } else if (Utils.isStringsEqual(property.getName(), "PAGE_ORIENTATION")) {
                    orientation = property.getValue();
                }
                if (height > 0 && width > 0 && orientation != null) {
                    if (Utils.isStringsEqual(orientation, "LANDSCAPE")) {
                        width = height;
                        count = new Integer(stjs.trunc(Math.floor(width / 24)) + 1);
                        if (count < 2) {
                            count = 2;
                        }
                        return ValueConverter.convertToType(count, ValueType.INTEGER, desiredOutputValueType);
                    }
                }
            }
        }
        return ValueConverter.convertToType(count, ValueType.INTEGER, desiredOutputValueType);
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var PropertyValueProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PropertyValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.propertyId = null;
    prototype.getPropertyId = function() {
        return this.propertyId;
    };
    prototype.setPropertyId = function(propertyId) {
        this.propertyId = propertyId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var property = null;
        var pit = context.getProduct().getProperties().iterator();
         while (pit.hasNext()){
            property = pit.next();
            if (Utils.convertToLongvalue(property.getId()) == Utils.convertToLongvalue(this.propertyId)) {
                if (desiredOutputValueType != null) 
                    return ValueConverter.convertToType(this.getPropertyValue(property), ValueType.STRING, desiredOutputValueType);
                 else 
                    return ValueConverter.convertToType(this.getPropertyValue(property), ValueType.STRING, this.getType());
            }
        }
        var fit = context.getProduct().getFeatures().iterator();
         while (fit.hasNext()){
            var ci = fit.next().getChoice();
            if (ci != null) {
                pit = ci.getProperties().iterator();
                 while (pit.hasNext()){
                    property = pit.next();
                    if (Utils.convertToLongvalue(property.getId()) == Utils.convertToLongvalue(this.propertyId)) {
                        if (desiredOutputValueType != null) 
                            return ValueConverter.convertToType(this.getPropertyValue(property), ValueType.STRING, desiredOutputValueType);
                         else 
                            return ValueConverter.convertToType(this.getPropertyValue(property), ValueType.STRING, this.getType());
                    }
                }
            }
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(0, ValueType.INTEGER, desiredOutputValueType);
        } else {
            return ValueConverter.convertToType(0, ValueType.INTEGER, this.getType());
        }
    };
    prototype.getPropertyValue = function(property) {
        if (property.getValue() != null) {
            return property.getValue();
        }
        return null;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContextChoiceProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContextChoiceProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        return ValueConverter.convertToLong(context.getSelectedChoiceIds(), ValueType.LONG);
    };
    prototype.getType = function() {
        return ValueType.LONG;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

/**
 *  @author 868908
 */
var CalcMultiConditionalValueProvider = function() {
    AbstractValueProvider.call(this);
    this.op = CalcOperator.ADD;
    this.conditionalValues = new ArrayList();
};
stjs.extend(CalcMultiConditionalValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.op = null;
    prototype.conditionalValues = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var value = null;
        try {
            if (this.conditionalValues.size() > 0) {
                switch (this.getType()) {
                    case ValueType.DECIMAL:
                        value = this.calcDecimals(context, desiredOutputValueType);
                        break;
                    case ValueType.INTEGER:
                        value = this.calcIntegers(context, desiredOutputValueType);
                        break;
                    case ValueType.STRING:
                        value = this.calcStrings(context, desiredOutputValueType);
                        break;
                    default:
                         throw new ValueException("Calculation not supported on value type: " + this.getType(), null);
                }
            }
        }catch (e) {
             throw new ValueException(e.getMessage(), e);
        }
        return value;
    };
    prototype.calcDecimals = function(context, desiredOutputValueType) {
        var value = null;
        if (this.conditionalValues.size() > 0) {
            var it = this.conditionalValues.iterator();
             while (it.hasNext()){
                var cv = it.next();
                if (cv.getCondition() == null || cv.getCondition().evaluate(context)) {
                    var v = cv.getValueProvider().getValueOfType(context, ValueType.DECIMAL);
                    switch (this.op) {
                        case CalcOperator.ADD:
                            if (value != null && v != null) {
                                value = value.add(v);
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.SUB:
                            if (value != null && v != null) {
                                value = value.subtract(v);
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.MULT:
                            if (value != null && v != null) {
                                value = value.multiply(v);
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.DIV:
                            if (value != null && v != null) {
                                value = value.divide(v);
                            } else {
                                value = v;
                            }
                            break;
                        default:
                             throw new ValueException(this.op + " not yet supported on Decimals", null);
                    }
                }
            }
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.DECIMAL, desiredOutputValueType);
        return null;
    };
    prototype.calcIntegers = function(context, desiredOutputValueType) {
        var value = null;
        if (this.conditionalValues.size() > 0) {
            var it = this.conditionalValues.iterator();
             while (it.hasNext()){
                var cv = it.next();
                if (cv.getCondition() == null || cv.getCondition().evaluate(context)) {
                    var v = cv.getValueProvider().getValueOfType(context, ValueType.INTEGER);
                    switch (this.op) {
                        case CalcOperator.ADD:
                            if (value != null && v != null) {
                                value = value + v;
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.SUB:
                            if (value != null && v != null) {
                                value = value - v;
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.MULT:
                            if (value != null && v != null) {
                                value = value * v;
                            } else {
                                value = v;
                            }
                            break;
                        case CalcOperator.DIV:
                            if (value != null && v != null) {
                                value = value / v;
                            } else {
                                value = v;
                            }
                            break;
                        default:
                             throw new ValueException(this.op + " not yet supported on Integers", null);
                    }
                }
            }
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.INTEGER, desiredOutputValueType);
        return null;
    };
    prototype.calcStrings = function(context, desiredOutputValueType) {
        var value = null;
        if (this.conditionalValues.size() > 0) {
            var it = this.conditionalValues.iterator();
             while (it.hasNext()){
                var cv = it.next();
                if (cv.getCondition() == null || cv.getCondition().evaluate(context)) {
                    var v = cv.getValueProvider().getValueOfType(context, ValueType.STRING);
                    switch (this.op) {
                        case CalcOperator.ADD:
                            if (value != null && v != null) {
                                value = value + v;
                            } else {
                                value = v;
                            }
                            break;
                        default:
                             throw new ValueException(this.op + " not supported with String types", null);
                    }
                }
            }
        }
        if (value != null) {
            return ValueConverter.convertToType(value, ValueType.STRING, desiredOutputValueType);
        }
        return null;
    };
    prototype.getOp = function() {
        return this.op;
    };
    prototype.setOp = function(operator) {
        this.op = operator;
    };
    prototype.getConditionalValues = function() {
        return this.conditionalValues;
    };
    prototype.setConditionalValues = function(values) {
        this.conditionalValues = values;
    };
}, {op: {name: "Enum", arguments: ["CalcOperator"]}, conditionalValues: {name: "List", arguments: ["ConditionalValue"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var ContentSILengthProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContentSILengthProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var itr = context.getProduct().getContentAssociations().iterator();
        var siLength = 0;
         while (itr.hasNext()){
            var ca = itr.next();
            if (ca != null) {
                if (ca.getSpecialInstructions() != null) {
                    if (ca.getSpecialInstructions().trim().length != 0) {
                        siLength += ca.getSpecialInstructions().length;
                    }
                }
            }
        }
        return ValueConverter.convertToInteger(siLength, this.getType());
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

/**
 *  Compares the result of two ValueProviders according to a ComparisonOperator (EQUALS, GREATER_THAN, etc...)
 *  @author cbochman
 */
var ComparisonCondition = function() {
    AbstractRuleElement.call(this);
    this.compOp = ComparisonOperator.EQUALS;
};
stjs.extend(ComparisonCondition, AbstractRuleElement, [Condition], function(constructor, prototype) {
    prototype.compOp = null;
    prototype.val1 = null;
    prototype.val2 = null;
    prototype.evaluate = function(context) {
        if (this.val1 != null && this.val2 != null) {
            var type = ValueConverter.getHighestType(this.val1.getType(), this.val2.getType());
            var result = 0;
            switch (type) {
                case ValueType.DECIMAL:
                    result = (this.val1.getValueOfType(context, type)).compareTo(this.val2.getValueOfType(context, type));
                    break;
                case ValueType.INTEGER:
                    result = (this.val1.getValueOfType(context, type)).compareTo(this.val2.getValueOfType(context, type));
                    break;
                case ValueType.STRING:
                    result = Utils.compareToStrings((this.val1.getValueOfType(context, type)), (this.val2.getValueOfType(context, type)));
                    break;
                case ValueType.LONG:
                    result = (this.val1.getValueOfType(context, type)).compareTo(this.val2.getValueOfType(context, type));
                    break;
                default:
                     throw new ConditionException("This condition class cannot handle " + this.val1.getType() + " comparisons", null);
            }
            return this.evalCompareResult(result);
        }
        return false;
    };
    prototype.evalCompareResult = function(compareResult) {
        switch (this.getCompOp()) {
            case ComparisonOperator.EQUALS:
                return (compareResult == 0);
            case ComparisonOperator.GREATER:
                return (compareResult > 0);
            case ComparisonOperator.GREATER_OR_EQUAL:
                return (compareResult >= 0);
            case ComparisonOperator.LESSER:
                return (compareResult < 0);
            case ComparisonOperator.LESSER_OR_EQUAL:
                return (compareResult <= 0);
            case ComparisonOperator.NOT_EQUALS:
                return (compareResult != 0);
            default:
                 throw new UnsupportedOperatorException("This class does not support operator (" + this.getCompOp() + ")", null);
        }
    };
    prototype.getCompOp = function() {
        return this.compOp;
    };
    prototype.setCompOp = function(operator) {
        this.compOp = operator;
    };
    prototype.getVal1 = function() {
        return this.val1;
    };
    prototype.setVal1 = function(valueProvider1) {
        this.val1 = valueProvider1;
    };
    prototype.getVal2 = function() {
        return this.val2;
    };
    prototype.setVal2 = function(valueProvider2) {
        this.val2 = valueProvider2;
    };
}, {compOp: {name: "Enum", arguments: ["ComparisonOperator"]}, val1: "ValueProvider", val2: "ValueProvider"});

var PageExceptionPageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageExceptionPageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var pageRange = null;
        var pageCount = 0;
        var pageRangeIt = context.getPageException().getRanges().iterator();
         while (pageRangeIt.hasNext()){
            pageRange = pageRangeIt.next();
            pageCount = pageCount + (pageRange.getEnd() - pageRange.getStart() + 1);
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, this.getType(), desiredOutputValueType);
        }
        return pageCount;
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ProductContextMapValueProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ProductContextMapValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.key = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var value = context.getValue(this.key);
        if (value == null) {
            value = "";
        }
        return ValueConverter.convertToType(value, this.getType(), desiredOutputValueType);
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
    prototype.getKey = function() {
        return this.key;
    };
    prototype.setKey = function(key) {
        this.key = key;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var DoubleSidePageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(DoubleSidePageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.elementType = null;
    prototype.getElementType = function() {
        return this.elementType;
    };
    prototype.setElementType = function(elementType) {
        this.elementType = elementType;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var productPageRanges = null;
        productPageRanges = PageRangeProcessor.splitpage(context, this.elementType);
        var isSingleSide = false;
        var isPrintOrInsertCover = false;
        var isFirstPageColor = false;
        var fis = null;
        if (this.elementType != null && this.elementType == ElementType.PAGEEXCEPTION) {
            fis = context.getPageException().getFeatures();
        } else {
            fis = context.getProduct().getFeatures();
        }
        for (var fi in fis) {
            for (var prpti in fi.getChoice().getProperties()) {
                if (("PRINT_FIRST_PAGE_COVER".equals(prpti.getName()) || "FIRST_PAGE_ON_CLEAR_BINDER".equals(prpti.getName())) && "YES".equalsIgnoreCase(prpti.getValue())) {
                    isPrintOrInsertCover = true;
                } else if ("PRINT_COLOR".equals(prpti.getName()) && "FIRST_COLOR_REMINAING_BLACK".equalsIgnoreCase(prpti.getValue())) {
                    isFirstPageColor = true;
                } else if ("SIDE".equals(prpti.getName()) && "SINGLE".equalsIgnoreCase(prpti.getValue())) {
                    isSingleSide = true;
                }
            }
        }
        var pageCount = 0;
        var pgItr = productPageRanges.iterator();
         while (pgItr.hasNext()){
            var range = pgItr.next();
            pageCount = pageCount + this.getPageCount(range, isSingleSide, isPrintOrInsertCover, isFirstPageColor);
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, desiredOutputValueType);
        } else {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, this.getType());
        }
    };
    prototype.getPageCount = function(range, isSingleSide, isPrintOrInsertCover, isFirstPageColor) {
        var pageCount = range.getEnd() - range.getStart() + 1;
        var doubleSidePageCount = 0;
        if (isSingleSide) {
            doubleSidePageCount = 0;
        } else {
            if (pageCount % 2 == 0) {
                if ((range.getStart() == 1) && (isFirstPageColor || isPrintOrInsertCover)) {
                    doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                } else {
                    doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart() + 1)) / 2);
                }
            } else {
                if (pageCount > 1) {
                    if ((range.getStart() == 1) && isPrintOrInsertCover) {
                        doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                    } else if ((range.getStart() == 1) && isFirstPageColor) {
                        doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart()) - 2) / 2);
                    } else {
                        doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                    }
                } else {
                    doubleSidePageCount = 0;
                }
            }
        }
        return doubleSidePageCount;
    };
}, {elementType: {name: "Enum", arguments: ["ElementType"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var TotalPageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(TotalPageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var productPageRanges = null;
        productPageRanges = PageRangeProcessor.splitpage(context, null);
        var fis = null;
        fis = context.getProduct().getFeatures();
        var pageCount = 0;
        pageCount = pageCount + this.getPageCount(productPageRanges, fis);
        var peIt = context.getProduct().getPageExceptions().iterator();
         while (peIt.hasNext()){
            var pe = peIt.next();
            var dContext = context;
            dContext.setPageException(pe);
            productPageRanges = PageRangeProcessor.splitpage(dContext, ElementType.PAGEEXCEPTION);
            fis = pe.getFeatures();
            pageCount = pageCount + this.getPageCount(productPageRanges, fis);
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, desiredOutputValueType);
        } else {
            return ValueConverter.convertToType(pageCount, ValueType.INTEGER, this.getType());
        }
    };
    prototype.getPageCount = function(productPageRanges, fis) {
        var isSingleSide = false;
        var isPrintOrInsertCover = false;
        var fIt = fis.iterator();
         while (fIt.hasNext()){
            var fi = fIt.next();
            var pIt = fi.getChoice().getProperties().iterator();
             while (pIt.hasNext()){
                var prpti = pIt.next();
                if (("PRINT_FIRST_PAGE_COVER".equals(prpti.getName()) || "FIRST_PAGE_ON_CLEAR_BINDER".equals(prpti.getName())) && "YES".equalsIgnoreCase(prpti.getValue())) {
                    isPrintOrInsertCover = true;
                } else if ("SIDE".equals(prpti.getName()) && "SINGLE".equalsIgnoreCase(prpti.getValue())) {
                    isSingleSide = true;
                }
            }
        }
        var pageCount = 0;
        var pgItr = productPageRanges.iterator();
         while (pgItr.hasNext()){
            var range = pgItr.next();
            pageCount = pageCount + this.getSingleSidePageCount(range, isSingleSide, isPrintOrInsertCover);
            pageCount = pageCount + this.getDoubleSidePageCount(range, isSingleSide, isPrintOrInsertCover);
        }
        return pageCount;
    };
    prototype.getSingleSidePageCount = function(range, isSingleSide, isPrintOrInsertCover) {
        var pageCount = range.getEnd() - range.getStart() + 1;
        if (isSingleSide) {
            if ((range.getStart() == 1) && isPrintOrInsertCover) {
                pageCount = pageCount - 1;
            }
        } else {
            if (pageCount % 2 == 0) {
                if ((range.getStart() == 1) && isPrintOrInsertCover) {
                    pageCount = 1;
                } else {
                    pageCount = 0;
                }
            } else {
                if ((range.getStart() == 1) && isPrintOrInsertCover) {
                    pageCount = 0;
                } else {
                    pageCount = 1;
                }
            }
        }
        return pageCount;
    };
    prototype.getDoubleSidePageCount = function(range, isSingleSide, isPrintOrInsertCover) {
        var pageCount = range.getEnd() - range.getStart() + 1;
        var doubleSidePageCount = 0;
        if (isSingleSide) {
            doubleSidePageCount = 0;
        } else {
            if (pageCount % 2 == 0) {
                if ((range.getStart() == 1) && isPrintOrInsertCover) {
                    doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                } else {
                    doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart() + 1)) / 2);
                }
            } else {
                if (pageCount > 1) {
                    if ((range.getStart() == 1) && isPrintOrInsertCover) {
                        doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                    } else {
                        doubleSidePageCount = stjs.trunc(((range.getEnd() - range.getStart())) / 2);
                    }
                } else {
                    doubleSidePageCount = 0;
                }
            }
        }
        return doubleSidePageCount;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ChoiceProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ChoiceProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.choiceId = null;
    prototype.containingPropertyName = null;
    prototype.getChoiceId = function() {
        return this.choiceId;
    };
    prototype.setChoiceId = function(choiceId) {
        this.choiceId = choiceId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        return this.choiceId != null ? ValueConverter.convertToLong(this.choiceId, ValueType.LONG) : this.getChoiceIdForProperty(context);
    };
    prototype.getType = function() {
        return ValueType.LONG;
    };
    prototype.getContainingPropertyName = function() {
        return this.containingPropertyName;
    };
    prototype.setContainingPropertyName = function(containingPropertyName) {
        this.containingPropertyName = containingPropertyName;
    };
    prototype.getChoiceIdForProperty = function(context) {
        var casList = context.getProduct().getContentAssociations();
        if (this.containingPropertyName != null && this.containingPropertyName == "PAGE_ORIENTATION") {
            var pageGroupList = casList.get(0).getPageGroups();
            var pg = pageGroupList.get(0);
            if (pg.getHeight() > pg.getWidth()) {
                return this.getChoiceIdByPrptyNameValue(this.containingPropertyName, "PORTRAIT", context);
            } else 
                return this.getChoiceIdByPrptyNameValue(this.containingPropertyName, "LANDSCAPE", context);
        } else if (this.containingPropertyName != null && this.containingPropertyName == "SIDE_VALUE") {
            var pageCount = this.getContentPageCount(casList);
            if (pageCount > 1) {
                return this.getChoiceIdByPrptyNameValue(this.containingPropertyName, "2", context);
            } else {
                return this.getChoiceIdByPrptyNameValue(this.containingPropertyName, "1", context);
            }
        } else if (this.containingPropertyName != null && (this.containingPropertyName == "MEDIA_HEIGHT" || this.containingPropertyName == "MEDIA_WEIGHT")) {
            var pageGroupList = casList.get(0).getPageGroups();
            var pg = pageGroupList.get(0);
            var cntReq = context.getCoreProduct().getContentRequirements().iterator().next();
            if (pg.getHeight() > pg.getWidth()) {
                return this.getChoiceIdForPageSize(cntReq, context, pg.getHeight(), pg.getWidth());
            } else {
                return this.getChoiceIdForPageSize(cntReq, context, pg.getWidth(), pg.getHeight());
            }
        }
        return null;
    };
    prototype.getChoiceIdByPrptyNameValue = function(propertyName, propertyValue, context) {
        var featureSet = context.getCoreProduct().getFeatures();
        var fiIt = featureSet.iterator();
         while (fiIt.hasNext()){
            var fi = fiIt.next();
            var chIt = fi.getChoices().iterator();
             while (chIt.hasNext()){
                var ch = chIt.next();
                var propertiesSet = ch.getProperties();
                var propertyIt = propertiesSet.iterator();
                 while (propertyIt.hasNext()){
                    var property = propertyIt.next();
                    if (Utils.isStringsEqual(propertyName, property.getName()) && Utils.isStringsEqual(propertyValue, property.getValue())) {
                        return ch.getId();
                    }
                }
            }
        }
        return null;
    };
    prototype.getChoiceIdForPageSize = function(cntReq, context, height, width) {
        var featureSet = context.getCoreProduct().getFeatures();
        var fiIt = featureSet.iterator();
         while (fiIt.hasNext()){
            var fi = fiIt.next();
            var chIt = fi.getChoices().iterator();
             while (chIt.hasNext()){
                var ch = chIt.next();
                var propertiesSet = ch.getProperties();
                var propertyIt = propertiesSet.iterator();
                var coreProductHeight = 0;
                var coreProductWidth = 0;
                 while (propertyIt.hasNext()){
                    var property = propertyIt.next();
                    if (Utils.isStringsEqual(property.getName(), "MEDIA_HEIGHT")) {
                        coreProductHeight = Utils.convertToFloat(property.getValue());
                    } else if (Utils.isStringsEqual(property.getName(), "MEDIA_WIDTH")) {
                        coreProductWidth = Utils.convertToFloat(property.getValue());
                    }
                    if (coreProductHeight > 0 && coreProductWidth > 0) {
                        if (this.matchingChoiceFoundForPageSize(height, width, cntReq, ch, coreProductHeight, coreProductWidth)) {
                            return ch.getId();
                        }
                    }
                }
            }
        }
        return null;
    };
    prototype.matchingChoiceFoundForPageSize = function(height, width, cntReq, ch, coreProductHeight, coreProductWidth) {
        var allowedHeightStartRange = new BigDecimal(coreProductHeight);
        var allowedHeightEndRange = new BigDecimal(coreProductHeight);
        var allowedWidthStartRange = new BigDecimal(coreProductWidth);
        var allowedWidthEndRange = new BigDecimal(coreProductWidth);
        if (cntReq.getBleedDimension() != null) {
            allowedHeightStartRange = allowedHeightStartRange.add(new BigDecimal(cntReq.getBleedDimension().getHeight().getStart()));
            allowedHeightEndRange = allowedHeightEndRange.add(new BigDecimal(cntReq.getBleedDimension().getHeight().getEnd()));
            allowedWidthStartRange = allowedWidthStartRange.add(new BigDecimal(cntReq.getBleedDimension().getWidth().getStart()));
            allowedWidthEndRange = allowedWidthEndRange.add(new BigDecimal(cntReq.getBleedDimension().getWidth().getEnd()));
        }
        var heightStartRangeCheck = (new BigDecimal(height)).compareTo(allowedHeightStartRange);
        var heightEndRangeCheck = (new BigDecimal(height)).compareTo(allowedHeightEndRange);
        var widthStartRangeCheck = (new BigDecimal(width)).compareTo(allowedWidthStartRange);
        var widthEndRangeCheck = (new BigDecimal(width)).compareTo(allowedWidthEndRange);
        if (heightStartRangeCheck >= 0 && heightEndRangeCheck <= 0) {
            if (widthStartRangeCheck >= 0 && widthEndRangeCheck <= 0) {
                return true;
            }
        }
        return false;
    };
    prototype.getContentPageCount = function(casList) {
        var pageCount = 0;
        var it = casList.iterator();
        var ca = null;
        var pageGroupIt = null;
        var pageGroup = null;
         while (it.hasNext()){
            ca = it.next();
            if (Utils.isStringsEqual("MAIN_CONTENT", ca.getPurpose()) || Utils.isStringsEqual("SINGLE_SHEET_FRONT", ca.getPurpose()) || Utils.isStringsEqual("SINGLE_SHEET_BACK", ca.getPurpose())) {
                pageGroupIt = it.next().getPageGroups().iterator();
                 while (pageGroupIt.hasNext()){
                    pageGroup = pageGroupIt.next();
                    pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart() + 1);
                }
            }
        }
        return pageCount;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContentAssociationCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContentAssociationCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var count = context.getProduct().getContentAssociations().size();
        return ValueConverter.convertToInteger(count, this.getType());
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var PageCountProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageCountProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.contentRequirementId = null;
    prototype.getContentRequirementId = function() {
        return this.contentRequirementId;
    };
    prototype.setContentRequirementId = function(contentRequirementId) {
        this.contentRequirementId = contentRequirementId;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var cas = this.getContentAssociationsOfProduct(context);
        var it = cas.iterator();
        var pageGroupIt = null;
        var pageGroup = null;
        var pageCount = 0;
         while (it.hasNext()){
            pageGroupIt = it.next().getPageGroups().iterator();
             while (pageGroupIt.hasNext()){
                pageGroup = pageGroupIt.next();
                pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart() + 1);
            }
        }
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(pageCount, this.getType(), desiredOutputValueType);
        }
        return pageCount;
    };
    prototype.getContentAssociationsOfProduct = function(context) {
        var cas = new ArrayList();
        if (this.contentRequirementId != null && this.contentRequirementId != 0) {
            this.getContentAssociationsForReqId(context.getProduct().getContentAssociations(), cas);
        }
        return cas;
    };
    prototype.getContentAssociationsForReqId = function(elementCas, cas) {
        var it = elementCas.iterator();
        var ca = null;
         while (it.hasNext()){
            ca = it.next();
            if (Utils.convertToLongvalue(ca.getContentReqId()) == Utils.convertToLongvalue(this.contentRequirementId)) {
                cas.add(ca);
            }
        }
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

/**
 *  @author 868908
 */
var CalcMultiValueProvider = function() {
    AbstractValueProvider.call(this);
    this.op = CalcOperator.ADD;
    this.values = new ArrayList();
};
stjs.extend(CalcMultiValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.op = null;
    prototype.values = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var value = null;
        if (this.values.size() > 0) {
            switch (this.getType()) {
                case ValueType.DECIMAL:
                    value = this.calcDecimals(context, desiredOutputValueType);
                    break;
                case ValueType.INTEGER:
                    value = this.calcIntegers(context, desiredOutputValueType);
                    break;
                case ValueType.STRING:
                    value = this.calcStrings(context, desiredOutputValueType);
                    break;
                default:
                     throw new ValueException("Calculation not supported on value type: " + this.getType(), null);
            }
        }
        return value;
    };
    prototype.calcDecimals = function(context, desiredOutputValueType) {
        var value = null;
        if (this.values.size() > 0) {
            var it = this.values.iterator();
             while (it.hasNext()){
                var vp = it.next();
                var v = vp.getValueOfType(context, ValueType.DECIMAL);
                switch (this.op) {
                    case CalcOperator.ADD:
                        if (value != null && v != null) {
                            value = value.add(v);
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.SUB:
                        if (value != null && v != null) {
                            value = value.subtract(v);
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.MULT:
                        if (value != null && v != null) {
                            value = value.multiply(v);
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.DIV:
                        if (value != null && v != null) {
                            value = value.divide(v);
                        } else {
                            value = v;
                        }
                        break;
                    default:
                         throw new ValueException(this.op + " not yet supported on Decimals", null);
                }
            }
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.DECIMAL, desiredOutputValueType);
        return null;
    };
    prototype.calcIntegers = function(context, desiredOutputValueType) {
        var value = null;
        if (this.values.size() > 0) {
            var it = this.values.iterator();
             while (it.hasNext()){
                var vp = it.next();
                var v = vp.getValueOfType(context, ValueType.INTEGER);
                switch (this.op) {
                    case CalcOperator.ADD:
                        if (value != null && v != null) {
                            value = value + v;
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.SUB:
                        if (value != null && v != null) {
                            value = value - v;
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.MULT:
                        if (value != null && v != null) {
                            value = value * v;
                        } else {
                            value = v;
                        }
                        break;
                    case CalcOperator.DIV:
                        if (value != null && v != null) {
                            value = value / v;
                        } else {
                            value = v;
                        }
                        break;
                    default:
                         throw new ValueException(this.op + " not yet supported on Integers", null);
                }
            }
        }
        if (value != null) 
            return ValueConverter.convertToType(value, ValueType.INTEGER, desiredOutputValueType);
        return null;
    };
    prototype.calcStrings = function(context, desiredOutputValueType) {
        var value = null;
        if (this.values.size() > 0) {
            var it = this.values.iterator();
             while (it.hasNext()){
                var vp = it.next();
                var v = vp.getValueOfType(context, ValueType.STRING);
                switch (this.op) {
                    case CalcOperator.ADD:
                        if (value != null && v != null) {
                            value = value + v;
                        } else {
                            value = v;
                        }
                        break;
                    default:
                         throw new ValueException(this.op + " not supported with String types", null);
                }
            }
        }
        if (value != null) {
            return ValueConverter.convertToType(value, ValueType.STRING, desiredOutputValueType);
        }
        return null;
    };
    prototype.getOp = function() {
        return this.op;
    };
    prototype.setOp = function(operator) {
        this.op = operator;
    };
    prototype.getValues = function() {
        return this.values;
    };
    prototype.setValues = function(values) {
        this.values = values;
    };
}, {op: {name: "Enum", arguments: ["CalcOperator"]}, values: {name: "List", arguments: ["ValueProvider"]}, type: {name: "Enum", arguments: ["ValueType"]}});

var SquareFootValueProvider = function() {
    AbstractValueProvider.call(this);
    this.inputUnit = DimensionUnit.INCH;
};
stjs.extend(SquareFootValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.inputUnit = null;
    prototype.val1 = null;
    prototype.val2 = null;
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var value = null;
        if (this.val1 != null && this.val2 != null) {
            switch (this.inputUnit) {
                case DimensionUnit.INCH:
                    value = this.calculateAreaForInch(context, desiredOutputValueType);
                    break;
                default:
                     throw new ValueException("Calculation not supported on input unit : " + this.inputUnit, null);
            }
        }
        return value;
    };
    prototype.calculateAreaForInch = function(context, desiredOutputValueType) {
        var value = null;
        var v1 = this.val1.getValueOfType(context, ValueType.DECIMAL);
        var v2 = this.val2.getValueOfType(context, ValueType.DECIMAL);
        var ftConvertor = new BigDecimal(12);
        v1 = v1.divide(ftConvertor, 2, RoundingMode.HALF_EVEN);
        v2 = v2.divide(ftConvertor, 2, RoundingMode.HALF_EVEN);
        value = (v1.multiply(v2)).setScale(2, RoundingMode.HALF_EVEN).doubleValue();
        if (value != null) {
            return ValueConverter.convertToType(value, ValueType.DECIMAL, desiredOutputValueType);
        }
        return null;
    };
    prototype.getInputUnit = function() {
        return this.inputUnit;
    };
    prototype.setInputUnit = function(inputUnit) {
        this.inputUnit = inputUnit;
    };
    prototype.getVal1 = function() {
        return this.val1;
    };
    prototype.setVal1 = function(valueProvider1) {
        this.val1 = valueProvider1;
    };
    prototype.getVal2 = function() {
        return this.val2;
    };
    prototype.setVal2 = function(valueProvider2) {
        this.val2 = valueProvider2;
    };
    prototype.getType = function() {
        return ValueType.DECIMAL;
    };
}, {inputUnit: {name: "Enum", arguments: ["DimensionUnit"]}, val1: "ValueProvider", val2: "ValueProvider", type: {name: "Enum", arguments: ["ValueType"]}});

var ProductUserNameValueProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ProductUserNameValueProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(this.getUserProductName(context.getProduct()), this.getType(), desiredOutputValueType);
        }
        return this.getUserProductName(context.getProduct());
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
    prototype.getUserProductName = function(productInstance) {
        if (productInstance.getUserProductName() != null) {
            return productInstance.getUserProductName();
        }
        return productInstance.getName();
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ProductQtyProvider = function() {
    AbstractRuleElement.call(this);
};
stjs.extend(ProductQtyProvider, AbstractRuleElement, [ValueProvider], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        if (desiredOutputValueType != null) {
            return ValueConverter.convertToType(context.getProduct().getQty(), this.getType(), desiredOutputValueType);
        }
        return context.getProduct().getQty();
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {});

var ContextContentProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(ContextContentProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        return ValueConverter.convertToInteger(context.isContentAdded(), ValueType.BOOLEAN);
    };
    prototype.getType = function() {
        return ValueType.INTEGER;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var PageExceptionStatusProvider = function() {
    AbstractValueProvider.call(this);
};
stjs.extend(PageExceptionStatusProvider, AbstractValueProvider, [], function(constructor, prototype) {
    prototype.pageExceptionIdList = null;
    prototype.getPageExceptionIdList = function() {
        return this.pageExceptionIdList;
    };
    prototype.setPageExceptionIdList = function(pageExceptionIdList) {
        this.pageExceptionIdList = pageExceptionIdList;
    };
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var containsPe = false;
        if (desiredOutputValueType != null) {
            var peList = context.getProduct().getPageExceptions();
            var it = peList.iterator();
             while (it.hasNext()){
                var pe = it.next();
                if (this.pageExceptionIdList.contains(pe.getId())) {
                    containsPe = true;
                    break;
                }
            }
        }
        return ValueConverter.convertToString(containsPe, this.getType());
    };
    prototype.getType = function() {
        return ValueType.BOOLEAN;
    };
}, {pageExceptionIdList: {name: "List", arguments: [null]}, type: {name: "Enum", arguments: ["ValueType"]}});

var ContentFirstWidthProvider = function() {
    ContentAssociationProvider.call(this);
};
stjs.extend(ContentFirstWidthProvider, ContentAssociationProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var width = 0;
        var ca = this.getFirstContentAssociation(context);
        if (ca != null) {
            var pageGroup = ca.getPageGroups().iterator().next();
            if (pageGroup != null) {
                width = pageGroup.getWidth();
            }
        }
        return ValueConverter.convertToDecimal(width, this.getType());
    };
    prototype.getType = function() {
        return ValueType.DECIMAL;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContentFirstHeightProvider = function() {
    ContentAssociationProvider.call(this);
};
stjs.extend(ContentFirstHeightProvider, ContentAssociationProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var height = 0;
        var ca = this.getFirstContentAssociation(context);
        if (ca != null) {
            var pageGroup = ca.getPageGroups().iterator().next();
            if (pageGroup != null) {
                height = pageGroup.getHeight();
            }
        }
        return ValueConverter.convertToDecimal(height, this.getType());
    };
    prototype.getType = function() {
        return ValueType.DECIMAL;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var PrintReadyStatusProvider = function() {
    ContentAssociationProvider.call(this);
};
stjs.extend(PrintReadyStatusProvider, ContentAssociationProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var isPrintReady = true;
        var isContentReqIdAvailable = (this.getContentRequirementId() != null) && (this.getContentRequirementId() != 0);
        isPrintReady = this.getPrintReadyStatus(context.getProduct().getContentAssociations(), isContentReqIdAvailable);
        if (!isPrintReady) {
            return ValueConverter.convertToString(isPrintReady, this.getType());
        }
        return ValueConverter.convertToString(isPrintReady, this.getType());
    };
    prototype.getPrintReadyStatus = function(cas, isContentReqIdAvailable) {
        var it = cas.iterator();
        var ca = null;
         while (it.hasNext()){
            ca = it.next();
            if (isContentReqIdAvailable && (Utils.convertToLongvalue(ca.getContentReqId()) == Utils.convertToLongvalue(this.getContentRequirementId()))) {
                if (!ca.isPrintReady()) {
                    return false;
                }
            } else {
                if (!ca.isPrintReady()) {
                    return false;
                }
            }
        }
        return true;
    };
    prototype.getType = function() {
        return ValueType.BOOLEAN;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var ContentFirstOrientationProvider = function() {
    ContentAssociationProvider.call(this);
};
stjs.extend(ContentFirstOrientationProvider, ContentAssociationProvider, [], function(constructor, prototype) {
    prototype.getValueOfType = function(context, desiredOutputValueType) {
        var orientation = null;
        var ca = this.getFirstContentAssociation(context);
        if (ca != null) {
            var pageGroup = ca.getPageGroups().iterator().next();
            if (pageGroup != null) {
                orientation = pageGroup.getOrientation().toString();
            }
        }
        return ValueConverter.convertToString(orientation, this.getType());
    };
    prototype.getType = function() {
        return ValueType.STRING;
    };
}, {type: {name: "Enum", arguments: ["ValueType"]}});

var LastSelectedChoicesCondition = function() {
    IdListCondition.call(this);
};
stjs.extend(LastSelectedChoicesCondition, IdListCondition, [], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        if (this.getIdList() != null && this.getIdList().size() > 0) {
            switch (this.getBooleanOperator()) {
                case BooleanOperator.AND:
                    return context.getProduct() != null && this.containsAll(context);
                case BooleanOperator.OR:
                    return context.getProduct() != null && this.containsAny(context);
                case BooleanOperator.NOT:
                    return context.getProduct() == null || this.containsNone(context);
                default:
                     throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
            }
        }
        return false;
    };
    prototype.containsAny = function(context) {
        var defaultChoiceIds = context.getDefaultOverrideChoiceIds();
        var selectedChoiceIds = context.getSelectedChoiceIds();
        var choiceId = null;
        if (defaultChoiceIds.isEmpty() && selectedChoiceIds.isEmpty()) {
            return false;
        }
        var it = this.getIdList().iterator();
         while (it.hasNext()){
            choiceId = it.next();
            if (defaultChoiceIds.contains(choiceId) || selectedChoiceIds.contains(choiceId)) 
                return true;
        }
        return false;
    };
    prototype.containsAll = function(context) {
        var defaultChoiceIds = context.getDefaultOverrideChoiceIds();
        var selectedChoiceIds = context.getSelectedChoiceIds();
        var choiceId = null;
        if (defaultChoiceIds.isEmpty() && selectedChoiceIds.isEmpty()) {
            return false;
        }
        var it = this.getIdList().iterator();
         while (it.hasNext()){
            choiceId = it.next();
            if (!defaultChoiceIds.contains(choiceId) && !selectedChoiceIds.contains(choiceId)) 
                return false;
        }
        return true;
    };
    prototype.containsNone = function(context) {
        var defaultChoiceIds = context.getDefaultOverrideChoiceIds();
        var selectedChoiceIds = context.getSelectedChoiceIds();
        var choiceId = null;
        if (defaultChoiceIds.isEmpty() && selectedChoiceIds.isEmpty()) {
            return true;
        }
        var it = this.getIdList().iterator();
         while (it.hasNext()){
            choiceId = it.next();
            if (defaultChoiceIds.contains(choiceId) || selectedChoiceIds.contains(choiceId)) 
                return false;
        }
        return true;
    };
}, {idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var PageExceptionCondition = function() {
    IdListCondition.call(this);
};
stjs.extend(PageExceptionCondition, IdListCondition, [], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        if (this.getIdList() != null && this.getIdList().size() > 0) {
            switch (this.getBooleanOperator()) {
                case BooleanOperator.AND:
                    return this.containsAllPageExceptions(this.getIdList(), context);
                case BooleanOperator.OR:
                    return this.containsAnyPageException(this.getIdList(), context);
                case BooleanOperator.NOT:
                    return this.containsNoPageException(this.getIdList(), context);
                default:
                     throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
            }
        }
        return false;
    };
    prototype.containsAllPageExceptions = function(Ids, context) {
        var pageExceptionId = null;
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            pageExceptionId = itLong.next();
            if (!this.containsPageException(pageExceptionId, context)) 
                return false;
        }
        return true;
    };
    prototype.containsAnyPageException = function(Ids, context) {
        var pageExceptionId = null;
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            pageExceptionId = itLong.next();
            if (this.containsPageException(pageExceptionId, context)) 
                return true;
        }
        return false;
    };
    prototype.containsNoPageException = function(Ids, context) {
        var pageExceptionId = null;
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            pageExceptionId = itLong.next();
            if (this.containsPageException(pageExceptionId, context)) 
                return false;
        }
        return true;
    };
    prototype.containsPageException = function(pageExceptionId, context) {
        var pe = null;
        var itPageExceptionInstance = context.getProduct().getPageExceptions().iterator();
         while (itPageExceptionInstance.hasNext()){
            pe = itPageExceptionInstance.next();
            if (pe.getId().equals(pageExceptionId)) {
                return true;
            }
        }
        return false;
    };
}, {idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var DefaultOverrideChoiceCondition = function() {
    IdListCondition.call(this);
};
stjs.extend(DefaultOverrideChoiceCondition, IdListCondition, [], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        if (this.getIdList() != null && this.getIdList().size() > 0) {
            switch (this.getBooleanOperator()) {
                case BooleanOperator.AND:
                    return context.getProduct() != null && this.containsAll(context.getDefaultOverrideChoiceIds());
                case BooleanOperator.OR:
                    return context.getProduct() != null && this.containsAny(context.getDefaultOverrideChoiceIds());
                case BooleanOperator.NOT:
                    return context.getProduct() == null || this.containsNone(context.getDefaultOverrideChoiceIds());
                default:
                     throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
            }
        }
        return false;
    };
    prototype.containsAny = function(choiceIds) {
        var choiceId = null;
        if (choiceIds == null || choiceIds.size() == 0) {
            return false;
        }
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (choiceIds.contains(choiceId)) 
                return true;
        }
        return false;
    };
    prototype.containsAll = function(choiceIds) {
        if (choiceIds == null || choiceIds.size() == 0) {
            return false;
        }
        var choiceId = null;
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (!choiceIds.contains(choiceId)) 
                return false;
        }
        return true;
    };
    prototype.containsNone = function(choiceIds) {
        var choiceId = null;
        var itLong = this.getIdList().iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (choiceIds.contains(choiceId)) 
                return false;
        }
        return true;
    };
}, {idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var ContentCondition = function() {
    IdListCondition.call(this);
};
stjs.extend(ContentCondition, IdListCondition, [], function(constructor, prototype) {
    prototype.evaluate = function(context) {
        if (this.getIdList() != null && this.getIdList().size() > 0) {
            switch (this.getBooleanOperator()) {
                case BooleanOperator.AND:
                    return context.getProduct() != null && context.getProduct().containsAllContentRequirements(this.getIdList());
                case BooleanOperator.OR:
                    return context.getProduct() != null && context.getProduct().containsAnyContentRequirement(this.getIdList());
                case BooleanOperator.NOT:
                    return context.getProduct() == null || context.getProduct().containsNoContentRequirement(this.getIdList());
                default:
                     throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
            }
        }
        return false;
    };
}, {idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var ChoicesCondition = function() {
    IdListCondition.call(this);
};
stjs.extend(ChoicesCondition, IdListCondition, [], function(constructor, prototype) {
    prototype.elementType = null;
    prototype.getElementType = function() {
        return this.elementType;
    };
    prototype.setElementType = function(elementType) {
        this.elementType = elementType;
    };
    prototype.evaluate = function(context) {
        if (this.getIdList() != null && this.getIdList().size() > 0) {
            if (this.elementType != null && this.elementType == ElementType.PAGEEXCEPTION) {
                switch (this.getBooleanOperator()) {
                    case BooleanOperator.AND:
                        return context.getPageException() != null && context.getPageException().containsAllChoices(this.getIdList());
                    case BooleanOperator.OR:
                        return context.getPageException() != null && context.getPageException().containsAnyChoices(this.getIdList());
                    case BooleanOperator.NOT:
                        return context.getPageException() == null || context.getPageException().containsNoChoices(this.getIdList());
                    default:
                         throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
                }
            } else {
                switch (this.getBooleanOperator()) {
                    case BooleanOperator.AND:
                        return context.getProduct() != null && context.getProduct().containsAllChoices(this.getIdList());
                    case BooleanOperator.OR:
                        return context.getProduct() != null && context.getProduct().containsAnyChoices(this.getIdList());
                    case BooleanOperator.NOT:
                        return context.getProduct() == null || context.getProduct().containsNoChoices(this.getIdList());
                    default:
                         throw new UnsupportedOperatorException("This class does not support operator (" + this.getBooleanOperator() + ")", null);
                }
            }
        }
        return false;
    };
}, {elementType: {name: "Enum", arguments: ["ElementType"]}, idList: {name: "Set", arguments: [null]}, booleanOperator: {name: "Enum", arguments: ["BooleanOperator"]}});

var ConfiguredPageException = function() {
    PageException.call(this);
};
stjs.extend(ConfiguredPageException, PageException, [], function(constructor, prototype) {
    prototype.selectable = false;
    prototype.isSelectable = function() {
        return this.selectable;
    };
    prototype.setSelectable = function(selectable) {
        this.selectable = selectable;
    };
}, {contentReqRefIds: {name: "Set", arguments: [null]}, featureRefs: {name: "List", arguments: ["FeatureReference"]}, features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}});

var ConfiguredFeature = function() {
    Feature.call(this);
};
stjs.extend(ConfiguredFeature, Feature, [], function(constructor, prototype) {
    prototype.selectable = false;
    prototype.isSelectable = function() {
        return this.selectable;
    };
    prototype.setSelectable = function(selectable) {
        this.selectable = selectable;
    };
}, {choices: {name: "Set", arguments: ["C"]}});

var FeatureInstance = function() {
    AbstractFeature.call(this);
};
stjs.extend(FeatureInstance, AbstractFeature, [], function(constructor, prototype) {
    prototype.choice = null;
    prototype.getChoice = function() {
        return this.choice;
    };
    prototype.setChoice = function(choice) {
        this.choice = choice;
    };
}, {choice: "ChoiceInstance"});

var ConfiguredChoice = function() {
    Choice.call(this);
};
stjs.extend(ConfiguredChoice, Choice, [], function(constructor, prototype) {
    prototype.selectable = false;
    prototype.isSelectable = function() {
        return this.selectable;
    };
    prototype.setSelectable = function(selectable) {
        this.selectable = selectable;
    };
}, {compatibilityGroups: {name: "Set", arguments: ["CompatibilityGroup"]}, properties: {name: "Set", arguments: ["P"]}});

var FlattenedProduct = function() {};
stjs.extend(FlattenedProduct, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.version = 0;
    prototype.instanceId = null;
    prototype.userProductName = null;
    prototype.qty = 0;
    prototype.contents = null;
    prototype.properties = null;
    prototype.pageExceptions = null;
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getUserProductName = function() {
        return this.userProductName;
    };
    prototype.setUserProductName = function(userProductName) {
        this.userProductName = userProductName;
    };
    prototype.getContents = function() {
        return this.contents;
    };
    prototype.setContents = function(contents) {
        this.contents = contents;
    };
    prototype.getProperties = function() {
        return this.properties;
    };
    prototype.setProperties = function(properties) {
        this.properties = properties;
    };
    prototype.getInstanceId = function() {
        return this.instanceId;
    };
    prototype.setInstanceId = function(instanceId) {
        this.instanceId = instanceId;
    };
    prototype.getPageExceptions = function() {
        return this.pageExceptions;
    };
    prototype.setPageExceptions = function(pageExceptions) {
        this.pageExceptions = pageExceptions;
    };
}, {contents: {name: "List", arguments: ["ContentAssociation"]}, properties: {name: "Map", arguments: [null, null]}, pageExceptions: {name: "List", arguments: ["FlattenedPageException"]}});

var ContentHint = function() {};
stjs.extend(ContentHint, null, [], function(constructor, prototype) {
    prototype.pageCount = 0;
    prototype.height = null;
    prototype.width = null;
    prototype.contentAssociation = null;
    prototype.getPageCount = function() {
        return this.pageCount;
    };
    prototype.setPageCount = function(pageCount) {
        this.pageCount = pageCount;
    };
    prototype.getHeight = function() {
        return this.height;
    };
    prototype.setHeight = function(height) {
        this.height = height;
    };
    prototype.getWidth = function() {
        return this.width;
    };
    prototype.setWidth = function(width) {
        this.width = width;
    };
    prototype.getContentAssociation = function() {
        return this.contentAssociation;
    };
    prototype.setContentAssociation = function(contentAssociation) {
        this.contentAssociation = contentAssociation;
    };
}, {contentAssociation: "ContentAssociation"});

var ProductDisplay = function() {};
stjs.extend(ProductDisplay, null, [], function(constructor, prototype) {
    prototype.id = null;
    prototype.name = null;
    prototype.version = 0;
    prototype.qty = 0;
    prototype.instanceId = null;
    prototype.userProductName = null;
    prototype.contentAssociations = null;
    prototype.productionContentAssociations = null;
    prototype.displayGroups = null;
    prototype.additionaldisplayGroups = null;
    prototype.skuDisplayGroups = null;
    prototype.printPageCount = 0;
    prototype.getId = function() {
        return this.id;
    };
    prototype.setId = function(id) {
        this.id = id;
    };
    prototype.getName = function() {
        return this.name;
    };
    prototype.setName = function(name) {
        this.name = name;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.getInstanceId = function() {
        return this.instanceId;
    };
    prototype.setInstanceId = function(instanceId) {
        this.instanceId = instanceId;
    };
    prototype.getUserProductName = function() {
        return this.userProductName;
    };
    prototype.setUserProductName = function(userProductName) {
        this.userProductName = userProductName;
    };
    prototype.getContentAssociations = function() {
        return this.contentAssociations;
    };
    prototype.setContentAssociations = function(contentAssociations) {
        this.contentAssociations = contentAssociations;
    };
    prototype.getDisplayGroups = function() {
        return this.displayGroups;
    };
    prototype.setDisplayGroups = function(displayGroups) {
        this.displayGroups = displayGroups;
    };
    prototype.getAdditionaldisplayGroups = function() {
        return this.additionaldisplayGroups;
    };
    prototype.setAdditionaldisplayGroups = function(additionaldisplayGroups) {
        this.additionaldisplayGroups = additionaldisplayGroups;
    };
    prototype.getProductionContentAssociations = function() {
        return this.productionContentAssociations;
    };
    prototype.setProductionContentAssociations = function(productionContentAssociations) {
        this.productionContentAssociations = productionContentAssociations;
    };
    prototype.getSkuDisplayGroups = function() {
        return this.skuDisplayGroups;
    };
    prototype.setSkuDisplayGroups = function(skuDisplayGroups) {
        this.skuDisplayGroups = skuDisplayGroups;
    };
    prototype.setPrintPageCount = function(printPageCount) {
        this.printPageCount = printPageCount;
    };
    prototype.getPrintPageCount = function() {
        return this.printPageCount;
    };
}, {contentAssociations: {name: "List", arguments: ["ContentAssociation"]}, productionContentAssociations: {name: "List", arguments: ["ProductionContentAssociation"]}, displayGroups: {name: "List", arguments: ["DisplayGroup"]}, additionaldisplayGroups: {name: "List", arguments: ["DisplayGroup"]}, skuDisplayGroups: {name: "List", arguments: ["SkuDisplayGroups"]}});

var ConfiguredProperty = function() {
    Property.call(this);
};
stjs.extend(ConfiguredProperty, Property, [], function(constructor, prototype) {
    prototype.overrideWithConfiguredValue = false;
    prototype.isOverrideWithConfiguredValue = function() {
        return this.overrideWithConfiguredValue;
    };
    prototype.setOverrideWithConfiguredValue = function(overrideWithConfiguredValue) {
        this.overrideWithConfiguredValue = overrideWithConfiguredValue;
    };
}, {bounds: {name: "Set", arguments: ["Bound"]}, bound: "Bound"});

var Product = function() {
    AbstractProduct.call(this);
    this.setFeatures(new ArraySet());
    this.setProperties(new ArraySet());
    this.contentRequirements = new ArraySet();
    this.setPageExceptions(new ArrayList());
};
stjs.extend(Product, AbstractProduct, [], function(constructor, prototype) {
    prototype.contentRequirements = null;
    prototype.externalRequirements = null;
    prototype.getContentRequirements = function() {
        return this.contentRequirements;
    };
    prototype.setContentRequirements = function(contentRequirements) {
        this.contentRequirements = contentRequirements;
    };
    prototype.getExternalRequirements = function() {
        return this.externalRequirements;
    };
    prototype.setExternalRequirements = function(externalRequirements) {
        this.externalRequirements = externalRequirements;
    };
    prototype.getChoiceById = function(id) {
        var feature = null;
        var choice = null;
        var itFeature = this.getFeatures().iterator();
         while (itFeature.hasNext()){
            feature = itFeature.next();
            choice = feature.getChoiceById(id);
            if (choice != null) 
                break;
        }
        return choice;
    };
    prototype.getPageExceptionById = function(id) {
        var itPageExs = this.getPageExceptions().iterator();
        var pageEx = null;
         while (itPageExs.hasNext()){
            pageEx = itPageExs.next();
            if (pageEx.getId() == id) {
                return pageEx;
            }
        }
        return pageEx;
    };
}, {contentRequirements: {name: "Set", arguments: ["ContentRequirement"]}, externalRequirements: "ExternalRequirements", features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}, pageExceptions: {name: "List", arguments: ["Pe"]}});

var DisplayHierarchy = function() {
    this.details = new ArrayList();
    this.refIdEntryMap = new ArrayMap();
};
stjs.extend(DisplayHierarchy, null, [], function(constructor, prototype) {
    prototype.entries = null;
    prototype.details = null;
    prototype.refIdEntryMap = null;
    prototype.addDisplaysDetails = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addDisplayDetails(it.next());
        }
    };
    prototype.addDisplayDetails = function(d) {
        this.details.add(d);
        this.refIdEntryMap.put(d.getRefId(), d);
    };
    prototype.getDisplayDetailsByRefId = function(refId) {
        return this.refIdEntryMap.get(refId);
    };
    prototype.getEntryDisplays = function() {
        return this.details;
    };
    prototype.getEntries = function() {
        return this.entries;
    };
    prototype.setEntries = function(entries) {
        this.entries = entries;
    };
}, {entries: {name: "List", arguments: ["DisplayEntry"]}, details: {name: "List", arguments: ["EntryDisplayDetails"]}, refIdEntryMap: {name: "Map", arguments: [null, "EntryDisplayDetails"]}});

var ProductDisplays = function() {
    this.refIdProductMap = new ArrayMap();
    this.idProductMap = new ArrayMap();
    this.idSkuMap = new ArrayMap();
    this.idValueDisplayMap = new ArrayMap();
    this.refIdPropertyMap = new ArrayMap();
    this.productDisplays = new ArrayList();
    this.propertyDisplays = new ArrayList();
    this.skuDisplays = new ArrayList();
    this.valueDisplays = new ArrayList();
    this.controlRefIdProductMap = new ArrayMap();
    this.controlRefIdPropertyMap = new ArrayMap();
};
stjs.extend(ProductDisplays, null, [], function(constructor, prototype) {
    prototype.productDisplays = null;
    prototype.propertyDisplays = null;
    prototype.idProductMap = null;
    prototype.idSkuMap = null;
    prototype.idValueDisplayMap = null;
    prototype.refIdProductMap = null;
    prototype.refIdPropertyMap = null;
    prototype.controlRefIdProductMap = null;
    prototype.controlRefIdPropertyMap = null;
    prototype.skuDisplays = null;
    prototype.valueDisplays = null;
    prototype.addProductDisplay = function(d) {
        this.productDisplays.add(d);
        this.addProductDisplayMap(d);
    };
    prototype.addSkuDisplay = function(d) {
        this.skuDisplays.add(d);
        this.addSkuDisplayMap(d);
    };
    prototype.addValueDisplay = function(d) {
        this.valueDisplays.add(d);
        this.addValueDisplayMap(d);
    };
    prototype.addControlIdProductDisplay = function(d) {
        this.productDisplays.add(d);
        this.addControlIdProductDisplayMap(d);
    };
    prototype.addProductDisplayMap = function(d) {
        this.refIdProductMap.put(d.getRefId(), d);
        this.idProductMap.put(d.getId(), d);
    };
    prototype.addSkuDisplayMap = function(d) {
        this.idSkuMap.put(d.getId(), d);
    };
    prototype.addValueDisplayMap = function(d) {
        this.idValueDisplayMap.put(d.getId(), d);
    };
    prototype.addControlIdProductDisplayMap = function(d) {
        this.controlRefIdProductMap.put(d.getRefId(), d);
        this.idProductMap.put(d.getId(), d);
    };
    prototype.addPropertyDisplay = function(d) {
        this.propertyDisplays.add(d);
        this.addPropertyDisplayMap(d);
    };
    prototype.addControlIdPropertyDisplay = function(d) {
        this.propertyDisplays.add(d);
        this.addControlIdPropertyDisplayMap(d);
    };
    prototype.addPropertyDisplayMap = function(pid) {
        this.refIdPropertyMap.put(pid.getRefId(), pid);
    };
    prototype.addControlIdPropertyDisplayMap = function(pid) {
        this.controlRefIdPropertyMap.put(pid.getRefId(), pid);
    };
    prototype.addProductDisplays = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addProductDisplay(it.next());
        }
    };
    prototype.addSkuDisplays = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addSkuDisplay(it.next());
        }
    };
    prototype.addValueDisplays = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addValueDisplay(it.next());
        }
    };
    prototype.addPropertyDisplays = function(d) {
        var it = d.iterator();
         while (it.hasNext()){
            this.addPropertyDisplay(it.next());
        }
    };
    prototype.removeProductDisplay = function(d) {
        var index = this.productDisplays.indexOf(d);
        this.productDisplays.remove(index);
        this.refIdProductMap.remove(d.getRefId());
        this.idProductMap.remove(d.getId());
    };
    prototype.removeSkuDisplay = function(d) {
        var index = this.skuDisplays.indexOf(d);
        this.skuDisplays.remove(index);
        this.idSkuMap.remove(d.getId());
    };
    prototype.removeValueDisplay = function(d) {
        var index = this.valueDisplays.indexOf(d);
        this.valueDisplays.remove(index);
        this.idValueDisplayMap.remove(d.getId());
    };
    prototype.removePropertyDisplay = function(d) {
        var index = this.propertyDisplays.indexOf(d);
        this.propertyDisplays.remove(index);
        this.refIdPropertyMap.remove(d.getRefId());
    };
    prototype.getProductDisplayByRefId = function(refId) {
        return this.refIdProductMap.get(refId);
    };
    prototype.getProductDisplayByControlRefId = function(refId) {
        return this.controlRefIdProductMap.get(refId);
    };
    prototype.getProductDisplayById = function(id) {
        return this.idProductMap.get(id);
    };
    prototype.getPropertyDisplayByRefId = function(refId) {
        return this.refIdPropertyMap.get(refId);
    };
    prototype.getPropertyDisplayByControlRefId = function(refId) {
        return this.controlRefIdPropertyMap.get(refId);
    };
    prototype.getPropertyDisplays = function() {
        return this.propertyDisplays;
    };
    prototype.getProductDisplays = function() {
        return this.productDisplays;
    };
    prototype.getSkuDisplayById = function(id) {
        return this.idSkuMap.get(id);
    };
    prototype.getValueDisplayById = function(id) {
        return this.idValueDisplayMap.get(id);
    };
    prototype.getSkuDisplays = function() {
        return this.skuDisplays;
    };
    prototype.getValueDisplays = function() {
        return this.valueDisplays;
    };
}, {productDisplays: {name: "List", arguments: ["ProductDisplayDetails"]}, propertyDisplays: {name: "List", arguments: ["PropertyInputDetails"]}, idProductMap: {name: "Map", arguments: [null, "ProductDisplayDetails"]}, idSkuMap: {name: "Map", arguments: [null, "SkuDisplayDetails"]}, idValueDisplayMap: {name: "Map", arguments: [null, "ValueDisplayDetails"]}, refIdProductMap: {name: "Map", arguments: [null, "ProductDisplayDetails"]}, refIdPropertyMap: {name: "Map", arguments: [null, "PropertyInputDetails"]}, controlRefIdProductMap: {name: "Map", arguments: [null, "ProductDisplayDetails"]}, controlRefIdPropertyMap: {name: "Map", arguments: [null, "PropertyInputDetails"]}, skuDisplays: {name: "List", arguments: ["SkuDisplayDetails"]}, valueDisplays: {name: "List", arguments: ["ValueDisplayDetails"]}});

var PageExceptionInstance = function() {
    AbstractPageException.call(this);
    this.ranges = new ArrayList();
    this.featureIdMap = new ArrayMap();
};
stjs.extend(PageExceptionInstance, AbstractPageException, [FeatureInstanceContainer], function(constructor, prototype) {
    prototype.hasContent = false;
    prototype.ranges = null;
    prototype.featureIdMap = null;
    prototype.instanceId = null;
    prototype.isHasContent = function() {
        return this.hasContent;
    };
    prototype.setHasContent = function(hasContent) {
        this.hasContent = hasContent;
    };
    prototype.getRanges = function() {
        return this.ranges;
    };
    prototype.setRanges = function(ranges) {
        this.ranges = ranges;
    };
    prototype.getInstanceId = function() {
        return this.instanceId;
    };
    prototype.setInstanceId = function(instanceId) {
        this.instanceId = instanceId;
    };
    prototype.addRange = function(range) {
        this.getRanges().add(range);
    };
    prototype.removeRange = function(range) {
        var index = this.getRanges().indexOf(range);
        this.getRanges().remove(index);
    };
    prototype.addFeature = function(feature) {
        this.getFeatures().add(feature);
        this.featureIdMap.put(feature.getId(), feature);
    };
    prototype.removeFeature = function(feature) {
        this.featureIdMap.remove(feature.getId());
        return this.getFeatures().remove(feature);
    };
    prototype.getFeatureById = function(id) {
        return this.featureIdMap.get(id);
    };
    prototype.containsFeature = function(featureId) {
        return this.featureIdMap.containsKey(featureId);
    };
    prototype.containsFeatureChoice = function(featureId, choiceId) {
        var feature = this.featureIdMap.get(featureId);
        if (feature != null) {
            if (feature.getChoice().getId() == choiceId) {
                return true;
            }
        }
        return false;
    };
    prototype.getChoiceById = function(choiceId) {
        var choice = null;
        var feature = null;
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            if (feature.getChoice().getId() == choiceId) {
                choice = feature.getChoice();
            }
            if (choice != null) 
                break;
        }
        return choice;
    };
    prototype.containsChoice = function(choiceId) {
        var feature = null;
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            if (feature.getChoice().getId() == Utils.convertToLongvalue(choiceId)) {
                return true;
            }
        }
        return false;
    };
    prototype.setFeatures = function(features) {
        var feature = null;
        AbstractPageException.prototype.setFeatures.call(this, features);
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            this.featureIdMap.put(feature.getId(), feature);
        }
    };
    prototype.setProperties = function(features) {
        AbstractPageException.prototype.setProperties.call(this, features);
    };
    prototype.containsAnyChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (this.containsChoice(choiceId)) 
                return true;
        }
        return false;
    };
    prototype.containsAllChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (!this.containsChoice(choiceId)) 
                return false;
        }
        return true;
    };
    prototype.containsNoChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (this.containsChoice(choiceId)) 
                return false;
        }
        return true;
    };
}, {ranges: {name: "List", arguments: ["PageRange"]}, featureIdMap: {name: "Map", arguments: [null, "FeatureInstance"]}, features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}});

var ProductInstance = function() {
    AbstractProduct.call(this);
    this.featureIdMap = new ArrayMap();
    this.contentAssociations = new ArrayList();
    this.productionContentAssociations = new ArrayList();
    this.userProductName = null;
    this.products = new ArrayList();
    this.externalSkus = new ArrayList();
    this.contextKeys = new ArraySet();
};
stjs.extend(ProductInstance, AbstractProduct, [FeatureInstanceContainer], function(constructor, prototype) {
    prototype.instanceId = null;
    prototype.userProductName = null;
    prototype.contentAssociations = null;
    /**
     *  Holds content information for print production(eg : Imposed content details)
     */
    prototype.productionContentAssociations = null;
    prototype.featureIdMap = null;
    prototype.catalogReference = null;
    prototype.products = null;
    /**
     *  New attributes added to support thirdparty products like HI-C
     */
    prototype.externalSkus = null;
    prototype.vendorReference = null;
    prototype.isOutSourced = false;
    prototype.contextKeys = null;
    prototype.externalProductionDetails = null;
    prototype.getInstanceId = function() {
        return this.instanceId;
    };
    prototype.setInstanceId = function(instanceId) {
        this.instanceId = instanceId;
    };
    prototype.getContentAssociations = function() {
        return this.contentAssociations;
    };
    prototype.setContentAssociations = function(contentAssociations) {
        this.contentAssociations = contentAssociations;
    };
    prototype.getProductionContentAssociations = function() {
        return this.productionContentAssociations;
    };
    prototype.setProductionContentAssociations = function(productionContentAssociations) {
        this.productionContentAssociations = productionContentAssociations;
    };
    prototype.getFeatureIdMap = function() {
        return this.featureIdMap;
    };
    prototype.setFeatureIdMap = function(featureIdMap) {
        this.featureIdMap = featureIdMap;
    };
    prototype.getCatalogReference = function() {
        return this.catalogReference;
    };
    prototype.setCatalogReference = function(catalogReference) {
        this.catalogReference = catalogReference;
    };
    prototype.containsAnyChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (this.containsChoice(choiceId)) 
                return true;
        }
        return false;
    };
    prototype.containsAllChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (!this.containsChoice(choiceId)) 
                return false;
        }
        return true;
    };
    prototype.containsNoChoices = function(choiceIds) {
        var choiceId = null;
        var itLong = choiceIds.iterator();
         while (itLong.hasNext()){
            choiceId = itLong.next();
            if (this.containsChoice(choiceId)) 
                return false;
        }
        return true;
    };
    prototype.containsAnyFeature = function(featureIds) {
        var featureId = null;
        var itLong = featureIds.iterator();
         while (itLong.hasNext()){
            featureId = itLong.next();
            if (this.containsFeature(featureId)) 
                return true;
        }
        return false;
    };
    prototype.containsNoFeature = function(featureIds) {
        var featureId = null;
        var itLong = featureIds.iterator();
         while (itLong.hasNext()){
            featureId = itLong.next();
            if (this.containsFeature(featureId)) 
                return false;
        }
        return true;
    };
    prototype.containsAllFeatures = function(featureIds) {
        var featureId = null;
        var itLong = featureIds.iterator();
         while (itLong.hasNext()){
            featureId = itLong.next();
            if (!this.containsFeature(featureId)) 
                return false;
        }
        return true;
    };
    prototype.containsFeature = function(featureId) {
        return this.featureIdMap.containsKey(featureId);
    };
    prototype.containsFeatureChoice = function(featureId, choiceId) {
        var feature = this.featureIdMap.get(featureId);
        if (feature != null) {
            return feature.getChoice().getId().equals(choiceId);
        }
        return false;
    };
    prototype.containsChoice = function(choiceId) {
        var feature = null;
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            if (feature.getChoice().getId().equals(choiceId)) {
                return true;
            }
        }
        return false;
    };
    prototype.getChoiceById = function(choiceId) {
        var choice = null;
        var feature = null;
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            if (feature.getChoice().getId().equals(choiceId)) {
                choice = feature.getChoice();
            }
            if (choice != null) 
                break;
        }
        return choice;
    };
    prototype.addFeature = function(feature) {
        this.getFeatures().add(feature);
        this.featureIdMap.put(feature.getId(), feature);
    };
    prototype.setFeatures = function(features) {
        var feature = null;
        AbstractProduct.prototype.setFeatures.call(this, features);
        var itFeatureInstance = this.getFeatures().iterator();
         while (itFeatureInstance.hasNext()){
            feature = itFeatureInstance.next();
            this.featureIdMap.put(feature.getId(), feature);
        }
    };
    prototype.removeFeature = function(feature) {
        this.featureIdMap.remove(feature.getId());
        return this.getFeatures().remove(feature);
    };
    prototype.removeFeaturebyId = function(featureId) {
        var feature = this.getFeatureById(featureId);
        if (feature != null) {
            return this.removeFeature(feature);
        }
        return false;
    };
    prototype.removeAllFeatures = function() {
        this.featureIdMap = new ArrayMap();
        this.setFeatures(new ArraySet());
    };
    prototype.getFeatureById = function(id) {
        return this.featureIdMap.get(id);
    };
    prototype.addContentAssociation = function(ca) {
        this.getContentAssociations().add(ca);
    };
    prototype.removeContentAssociation = function(ca) {
        var index = this.getContentAssociations().indexOf(ca);
        this.getContentAssociations().remove(index);
    };
    prototype.addPageException = function(fe) {
        this.getPageExceptions().add(fe);
    };
    prototype.removePageException = function(fe) {
        var index = this.getPageExceptions().indexOf(fe);
        this.getPageExceptions().remove(index);
    };
    prototype.removePageExceptionAt = function(index) {
        this.getPageExceptions().remove(index);
    };
    prototype.removeAllPageExceptions = function() {
        this.getPageExceptions().clear();
    };
    prototype.containsAllContentRequirements = function(contentReqIds) {
        var reqId = null;
        var itLong = contentReqIds.iterator();
         while (itLong.hasNext()){
            reqId = itLong.next();
            if (!this.containsContentRequirement(reqId)) 
                return false;
        }
        return true;
    };
    prototype.containsContentRequirement = function(reqId) {
        var contentAssocIt = this.contentAssociations.iterator();
         while (contentAssocIt.hasNext()){
            var contentAssoc = contentAssocIt.next();
            if (contentAssoc.getContentReqId().equals(reqId)) 
                return true;
        }
        return false;
    };
    prototype.containsAnyContentRequirement = function(contentReqIds) {
        var reqId = null;
        var itLong = contentReqIds.iterator();
         while (itLong.hasNext()){
            reqId = itLong.next();
            if (this.containsContentRequirement(reqId)) 
                return true;
        }
        return false;
    };
    prototype.containsNoContentRequirement = function(contentReqIds) {
        var reqId = null;
        var itLong = contentReqIds.iterator();
         while (itLong.hasNext()){
            reqId = itLong.next();
            if (this.containsContentRequirement(reqId)) 
                return false;
        }
        return true;
    };
    prototype.getUserProductName = function() {
        return this.userProductName;
    };
    prototype.setUserProductName = function(userProductName) {
        this.userProductName = userProductName;
    };
    prototype.getProducts = function() {
        return this.products;
    };
    prototype.setProducts = function(products) {
        this.products = products;
    };
    prototype.getIsOutSourced = function() {
        return this.isOutSourced;
    };
    prototype.setIsOutSourced = function(isOutSourced) {
        this.isOutSourced = isOutSourced;
    };
    /**
     *  Changes For HI-C
     */
    prototype.getExternalSkus = function() {
        return this.externalSkus;
    };
    prototype.setExternalSkus = function(externalSkus) {
        this.externalSkus = externalSkus;
    };
    prototype.getVendorReference = function() {
        return this.vendorReference;
    };
    prototype.setVendorReference = function(vendorReference) {
        this.vendorReference = vendorReference;
    };
    prototype.getContextKeys = function() {
        return this.contextKeys;
    };
    prototype.setContextKeys = function(contextKeys) {
        this.contextKeys = contextKeys;
    };
    prototype.addExternalSku = function(externalSku) {
        this.getExternalSkus().add(externalSku);
    };
    prototype.addContextKeys = function(contextKeys) {
        this.getContextKeys().add(contextKeys);
    };
    prototype.getExternalProductionDetails = function() {
        return this.externalProductionDetails;
    };
    prototype.setExternalProductionDetails = function(externalProductionDetails) {
        this.externalProductionDetails = externalProductionDetails;
    };
    prototype.removeAllExternalSkus = function() {
        this.getExternalSkus().clear();
    };
}, {contentAssociations: {name: "List", arguments: ["ContentAssociation"]}, productionContentAssociations: {name: "List", arguments: ["ProductionContentAssociation"]}, featureIdMap: {name: "Map", arguments: [null, "FeatureInstance"]}, catalogReference: "CatalogReference", products: {name: "List", arguments: ["ProductInstance"]}, externalSkus: {name: "List", arguments: ["ExternalSku"]}, vendorReference: "VendorReference", contextKeys: {name: "Set", arguments: [null]}, externalProductionDetails: "ExternalProductionDetails", features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}, pageExceptions: {name: "List", arguments: ["Pe"]}});

/**
 *  This class will process the display request for job Tickets
 *  
 *  @author 973901
 */
var ProductDisplayProcessor = function() {};
stjs.extend(ProductDisplayProcessor, null, [], function(constructor, prototype) {
    /**
     *  This method provides product Display for given product instance and control Id
     *  
     *  @param productData
     *  @param productinstance
     *  @param controlId
     *  @return
     *  @throws ProductDisplayProcessorException
     */
    prototype.productDisplay = function(productData, productInstance, controlId) {
        var cfgDisplayDetails = productData.getDisplays();
        this.updateDisplayDetailsMap(cfgDisplayDetails, controlId);
        var displayGroups = this.buildDisplayGroups(productInstance, controlId, productData);
        var additionalDisplayGroups = this.buildAdditionalDisplayGroups(productInstance, controlId, cfgDisplayDetails);
        var skuDisplayGroups = this.buildSkuDisplayGroups(productInstance, controlId, productData);
        displayGroups = this.filterEmptyDisplayGroups(displayGroups);
        additionalDisplayGroups = this.filterEmptyDisplayGroups(additionalDisplayGroups);
        return this.buildProductDisplay(productInstance, displayGroups, additionalDisplayGroups, cfgDisplayDetails, controlId, skuDisplayGroups);
    };
    /**
     *  This method provides display Groups using product instance, control Id and DisplayGroup Map. This
     *  method covers features and choices present in product instance
     *  
     *  @param productinstance
     *  @param controlId
     *  @param cfgDisplayDetails
     *  @param displayGroups
     */
    prototype.buildDisplayGroups = function(productinstance, controlId, productData) {
        var cfgDisplayDetails = productData.getDisplays();
        var fiIt = productinstance.getFeatures().iterator();
        var displayGroups = new ArrayList();
        var displayGroupMap = new ArrayMap();
         while (fiIt.hasNext()){
            var fi = fiIt.next();
            var displayDetails = this.retrieveProductDisplayDetails(fi.getId(), controlId, cfgDisplayDetails);
            var featureDisplayGroup = null;
            if (displayDetails != null) {
                featureDisplayGroup = new DisplayGroup();
                featureDisplayGroup.setId(fi.getId());
                featureDisplayGroup.setName(displayDetails.getName());
                displayGroupMap.put(fi.getId(), featureDisplayGroup);
                var displayDetailsChoice = this.retrieveProductDisplayDetails(fi.getChoice().getId(), controlId, cfgDisplayDetails);
                if (displayDetailsChoice != null) {
                    featureDisplayGroup.setValue(displayDetailsChoice.getName());
                }
                this.assignParentDisplayGroup(displayDetails.getParentId(), controlId, featureDisplayGroup, displayGroups, displayGroupMap, cfgDisplayDetails);
            }
            this.buildDisplayGroupWithProperties(controlId, fi.getChoice(), featureDisplayGroup, displayGroups, displayGroupMap, cfgDisplayDetails);
        }
        displayGroups.addAll(this.buildPageExceptionDisplayGroup(productinstance, controlId, productData, displayGroupMap));
        return displayGroups;
    };
    /**
     *  This method provides display group for tabs and inserts using page exceptions.
     *  
     *  @param pageExceptions
     *  @param cfgDisplayDetails
     *  @param controlId
     *  @param displayGroups2
     *  @return
     */
    prototype.buildPageExceptionDisplayGroup = function(productinstance, controlId, productData, productDisplayGroupMap) {
        var cfgDisplayDetails = productData.getDisplays();
        var pageExceptions = productinstance.getPageExceptions();
        var pageTypeList = this.buildPageTypeList(pageExceptions);
        this.sortPages(pageTypeList);
        var displayGroups = new ArrayList();
        var displayGroupMap = new ArrayMap();
        var peIterator = pageExceptions.iterator();
         while (peIterator.hasNext()){
            var peInstance = peIterator.next();
            var peDisplayGroup = null;
            var displayDetails = this.retrieveProductDisplayDetails(peInstance.getId(), controlId, cfgDisplayDetails);
            if (displayDetails != null) {
                peDisplayGroup = new DisplayGroup();
                peDisplayGroup.setId(displayDetails.getId());
                peDisplayGroup.setName(displayDetails.getName());
                this.assignParentDisplayGroup(displayDetails.getParentId(), controlId, peDisplayGroup, displayGroups, displayGroupMap, cfgDisplayDetails);
            }
            var feIterator = peInstance.getFeatures().iterator();
            var featureIds = new ArrayList();
             while (feIterator.hasNext()){
                var feature = feIterator.next();
                featureIds.add(feature.getId());
                var feDisplayDetails = this.retrieveProductDisplayDetails(feature.getId(), controlId, cfgDisplayDetails);
                var feDisplayGroup = null;
                if (feDisplayDetails != null) {
                    feDisplayGroup = new DisplayGroup();
                    feDisplayGroup.setId(feDisplayDetails.getId());
                    feDisplayGroup.setName(feDisplayDetails.getName());
                    var choiceDisplayDetails = this.retrieveProductDisplayDetails(feature.getChoice().getId(), controlId, cfgDisplayDetails);
                    if (choiceDisplayDetails != null) {
                        feDisplayGroup.setValue(choiceDisplayDetails.getName());
                    }
                    if (peDisplayGroup != null) {
                        peDisplayGroup.getDisplayGroups().add(feDisplayGroup);
                    }
                }
                var propertyDisplayGroups = this.buildDisplayGroupForPageExeProperties(feature, pageTypeList, controlId, cfgDisplayDetails);
                if (feDisplayGroup != null) {
                    feDisplayGroup.getDisplayGroups().addAll(propertyDisplayGroups);
                } else if (peDisplayGroup != null) {
                    peDisplayGroup.getDisplayGroups().addAll(propertyDisplayGroups);
                }
            }
            if (this.isPrintException(peInstance.getProperties())) {
                peDisplayGroup.getDisplayGroups().addAll(this.buildAdditionalFeatureExceptionDisplay(featureIds, productDisplayGroupMap, productData));
                var propIterator = peInstance.getProperties().iterator();
                 while (propIterator.hasNext()){
                    var property = propIterator.next();
                    var propertyDisplay = this.retrievePropertyDisplayDetails(property.getId(), controlId, cfgDisplayDetails);
                    if (propertyDisplay != null) {
                        peDisplayGroup.getDisplayGroups().add(this.buildPropertyDisplayGroup(property, propertyDisplay));
                    }
                }
            }
            this.buildPlacementForPageExceptions(pageTypeList, peDisplayGroup.getDisplayGroups(), peInstance.getRanges());
        }
        return displayGroups;
    };
    prototype.isPrintException = function(properties) {
        var iterator = properties.iterator();
         while (iterator.hasNext()){
            var propInstance = iterator.next();
            if ("EXCEPTION_TYPE".equals(propInstance.getName()) && "PRINTING_EXCEPTION".equals(propInstance.getValue())) {
                return true;
            }
        }
        return false;
    };
    prototype.buildAdditionalFeatureExceptionDisplay = function(featureIds, productDisplayGroupMap, productData) {
        var printExceptionFeatureIds = new ArrayList();
        var peIt = productData.getProduct().getPageExceptions().iterator();
        var displayGroups = new ArrayList();
         while (peIt.hasNext()){
            var pe = peIt.next();
            var prptIterator = pe.getProperties().iterator();
             while (prptIterator.hasNext()){
                var property = prptIterator.next();
                if ("EXCEPTION_TYPE".equals(property.getName()) && "PRINTING_EXCEPTION".equals(property.getValue())) {
                    var feRefIt = pe.getFeatureRefs().iterator();
                     while (feRefIt.hasNext()){
                        printExceptionFeatureIds.add(feRefIt.next().getFeatureId());
                    }
                    var feIt = pe.getFeatures().iterator();
                     while (feIt.hasNext()){
                        printExceptionFeatureIds.add(feIt.next().getId());
                    }
                    printExceptionFeatureIds.removeAll(featureIds);
                    var printExeFeatureIter = printExceptionFeatureIds.iterator();
                     while (printExeFeatureIter.hasNext()){
                        var featureId = printExeFeatureIter.next();
                        if (productDisplayGroupMap.get(featureId) != null) {
                            displayGroups.add(productDisplayGroupMap.get(featureId));
                        }
                    }
                    break;
                }
            }
        }
        return displayGroups;
    };
    /**
     *  This method provides display group properties for tabs and inserts using page exceptions feature.
     *  
     *  @param feature
     *  @param pageTypeList
     *  @param peRange
     *  @param insertCount
     *  @return
     */
    prototype.buildDisplayGroupForPageExeProperties = function(feature, pageTypeList, controlId, cfgDisplayDetails) {
        var displayGroups = new ArrayList();
        if (feature.getChoice().getProperties() != null) {
            var propIterator = feature.getChoice().getProperties().iterator();
             while (propIterator.hasNext()){
                var property = propIterator.next();
                var propertyDisplay = this.retrievePropertyDisplayDetails(property.getId(), controlId, cfgDisplayDetails);
                if (propertyDisplay != null) {
                    displayGroups.add(this.buildPropertyDisplayGroup(property, propertyDisplay));
                }
            }
        }
        return displayGroups;
    };
    /**
     *  This method provides placement for tabs and inserts using page exceptions.
     *  
     *  @param pageTypeList
     *  @param displayGroups
     *  @param peRange
     *  @param insertCount
     */
    prototype.buildPlacementForPageExceptions = function(pageTypeList, displayGroups, peRanges) {
        var printExePageRanges = new ArrayList();
        var prIterator = peRanges.iterator();
         while (prIterator.hasNext()){
            var range = prIterator.next();
            var insertCount = 0;
            var pageTypeIter = pageTypeList.iterator();
             while (pageTypeIter.hasNext()){
                var pageType = pageTypeIter.next();
                if (pageType.getRange() != null && pageType.getRange().getStart() == range.getStart() && pageType.getRange().getEnd() == range.getEnd()) {
                    var placementDisplayGroup = new DisplayGroup();
                    placementDisplayGroup.setId((new Date()).getTime());
                    if (PageExceptionType.INSERT == pageType.getPageExceptionType()) {
                        placementDisplayGroup.setName("Placement");
                        placementDisplayGroup.setValue("Before " + (pageType.getPageNumber() - insertCount));
                        displayGroups.add(placementDisplayGroup);
                    } else if (PageExceptionType.TAB == pageType.getPageExceptionType()) {
                        placementDisplayGroup.setName("Page");
                        placementDisplayGroup.setValue((pageType.getPageNumber() - insertCount) + "");
                        displayGroups.add(placementDisplayGroup);
                    } else if (PageExceptionType.PRINT_EXCEPTION == pageType.getPageExceptionType()) {
                        if (!printExePageRanges.isEmpty() && ((printExePageRanges.get(printExePageRanges.size() - 1).getEnd() + 1) == (range.getStart() - insertCount))) {
                            printExePageRanges.get(printExePageRanges.size() - 1).setEnd(range.getEnd() - insertCount);
                        } else {
                            var printExeRange = new PageRange();
                            printExeRange.setStart(range.getStart() - insertCount);
                            printExeRange.setEnd(range.getEnd() - insertCount);
                            printExePageRanges.add(printExeRange);
                        }
                    }
                }
                if (PageExceptionType.INSERT == pageType.getPageExceptionType()) {
                    insertCount++;
                }
            }
        }
        if (!printExePageRanges.isEmpty()) {
            var placementDisplayGroup = new DisplayGroup();
            placementDisplayGroup.setId((new Date()).getTime());
            placementDisplayGroup.setName("Pages");
            prIterator = printExePageRanges.iterator();
            var value = new String();
             while (prIterator.hasNext()){
                var range = prIterator.next();
                if (range.getStart() == range.getEnd()) {
                    value = value + range.getStart() + " ";
                } else {
                    value = value + range.getStart() + "-" + range.getEnd() + " ";
                }
            }
            placementDisplayGroup.setValue(value);
            displayGroups.add(placementDisplayGroup);
        }
    };
    /**
     *  This method create the page numbers for placement using product exceptions.
     *  
     *  @param pageExceptions
     *  @return
     */
    prototype.buildPageTypeList = function(pageExceptions) {
        var pageTypeList = new ArrayList();
        var peIterator = pageExceptions.iterator();
         while (peIterator.hasNext()){
            var peInstance = peIterator.next();
            var propIterator = peInstance.getProperties().iterator();
             while (propIterator.hasNext()){
                var property = propIterator.next();
                if ("EXCEPTION_TYPE".equals(property.getName())) {
                    if ("TAB".equals(property.getValue())) {
                        this.buildPagesWithRange(pageTypeList, peInstance, PageExceptionType.TAB);
                    } else if ("INSERT".equals(property.getValue())) {
                        this.buildPagesWithRange(pageTypeList, peInstance, PageExceptionType.INSERT);
                    } else if ("PRINTING_EXCEPTION".equals(property.getValue())) {
                        this.buildPagesWithRange(pageTypeList, peInstance, PageExceptionType.PRINT_EXCEPTION);
                    }
                }
            }
        }
        return pageTypeList;
    };
    /**
     *  This method sort the given page numbers to ascending order.
     *  
     *  @param pageTypeList
     */
    prototype.sortPages = function(pageTypeList) {
        var copyList = new ArrayList();
        copyList.addAll(pageTypeList);
        var itrKey = pageTypeList.iterator();
         while (itrKey.hasNext()){
            var pei = itrKey.next();
            var itrCopyList = copyList.iterator();
             while (itrCopyList.hasNext()){
                var copypei = itrCopyList.next();
                if (pei.getPageNumber() < copypei.getPageNumber()) {
                    var temp = new PageType(pei.getPageNumber(), pei.getPageExceptionType(), pei.getRange());
                    pei.setPageType(copypei.getPageNumber(), copypei.getPageExceptionType(), copypei.getRange());
                    copypei.setPageType(temp.getPageNumber(), temp.getPageExceptionType(), temp.getRange());
                }
            }
        }
    };
    /**
     *  This method updates pages including range with page type, page exception and page exception type.
     *  
     *  @param pageTypeList
     *  @param peInstance
     *  @param type
     */
    prototype.buildPagesWithRange = function(pageTypeList, peInstance, type) {
        var rangeIt = peInstance.getRanges().iterator();
         while (rangeIt.hasNext()){
            var range = rangeIt.next();
            if (PageExceptionType.PRINT_EXCEPTION == type) {
                pageTypeList.add(new PageType(range.getStart(), type, range));
            } else {
                var count = range.getStart();
                 while (count <= range.getEnd()){
                    pageTypeList.add(new PageType(count, type, range));
                    count++;
                }
            }
        }
    };
    /**
     *  This method provides display Groups for properties using product instance, control Id and
     *  DisplayGroup Map. This method covers properties available at Product level
     *  
     *  @param productinstance
     *  @param controlId
     *  @param cfgDisplayDetails
     */
    prototype.buildAdditionalDisplayGroups = function(productInstance, controlId, cfgDisplayDetails) {
        var displayGroupMap = new ArrayMap();
        var additionalDisplayGroups = new ArrayList();
        var piIt = productInstance.getProperties().iterator();
         while (piIt.hasNext()){
            var pi = piIt.next();
            var propertyInputDetails = this.retrievePropertyDisplayDetails(pi.getId(), controlId, cfgDisplayDetails);
            if (propertyInputDetails != null) {
                var displayGroup = this.buildPropertyDisplayGroup(pi, propertyInputDetails);
                displayGroupMap.put(pi.getId(), displayGroup);
                this.assignParentDisplayGroup(propertyInputDetails.getParentId(), controlId, displayGroup, additionalDisplayGroups, displayGroupMap, cfgDisplayDetails);
            }
        }
        this.buildFileInstructions(productInstance, additionalDisplayGroups);
        return additionalDisplayGroups;
    };
    /**
     *  This method build the file instructions with product instance.
     *  
     *  @param productInstance
     *  @param additionalDisplayGroups
     */
    prototype.buildFileInstructions = function(productInstance, additionalDisplayGroups) {
        var iterator = productInstance.getContentAssociations().iterator();
        var contentRefIds = new ArraySet();
         while (iterator.hasNext()){
            var contentAssociation = iterator.next();
            if (contentAssociation != null && contentAssociation.getSpecialInstructions() != null && (!contentRefIds.contains(contentAssociation.getContentReference()))) {
                contentRefIds.add(contentAssociation.getContentReference());
                var fileInstructions = new DisplayGroup();
                fileInstructions.setId((new Date()).getTime());
                fileInstructions.setName("File Instructions");
                fileInstructions.setValue(contentAssociation.getSpecialInstructions());
                additionalDisplayGroups.add(fileInstructions);
            }
        }
    };
    /**
     *  This method builds the display group for properties inside choices
     *  
     *  @param featureId
     *  @param displayDetails
     *  @param choice
     *  @param displayGroups
     *  @param controlId
     *  @param parentDisplayMap
     *  @param cfgDisplayDetails
     *  @param featureDisplayList
     */
    prototype.buildDisplayGroupWithProperties = function(controlId, choice, featureDisplayGroup, displayGroups, displayGroupMap, cfgProductDisplays) {
        var propertyInstance = choice.getProperties().iterator();
         while (propertyInstance.hasNext()){
            var pi = propertyInstance.next();
            var propertyInputDetails = this.retrievePropertyDisplayDetails(pi.getId(), controlId, cfgProductDisplays);
            if (propertyInputDetails != null) {
                if (propertyInputDetails.getParentId() != null && propertyInputDetails.getParentId() != 0) {
                    var displayGroup = this.buildPropertyDisplayGroup(pi, propertyInputDetails);
                    displayGroupMap.put(pi.getId(), displayGroup);
                    this.assignParentDisplayGroup(propertyInputDetails.getParentId(), controlId, displayGroup, displayGroups, displayGroupMap, cfgProductDisplays);
                } else if (featureDisplayGroup != null) {
                    var displayGroup = this.buildPropertyDisplayGroup(pi, propertyInputDetails);
                    featureDisplayGroup.getDisplayGroups().add(displayGroup);
                }
            }
        }
    };
    /**
     *  This method builds the display groups for properties
     *  
     *  @param pi
     *  @param propertyInputDetails
     *  @return
     */
    prototype.buildPropertyDisplayGroup = function(pi, propertyInputDetails) {
        var displayGroup = new DisplayGroup();
        displayGroup.setId(pi.getId());
        displayGroup.setName(propertyInputDetails.getName());
        this.buildPropertyDisplayValue(pi.getValue(), propertyInputDetails, displayGroup);
        return displayGroup;
    };
    /**
     *  This method retrieves display details for properties received in choice instance
     *  
     *  @param id
     *  @param controlId
     *  @param cfgDisplayDetails
     *  @return
     */
    prototype.retrievePropertyDisplayDetails = function(id, controlId, cfgDisplayDetails) {
        var propertyDisplayDetails = cfgDisplayDetails.getPropertyDisplayByControlRefId(id);
        if (propertyDisplayDetails == null) {
            propertyDisplayDetails = cfgDisplayDetails.getPropertyDisplayByRefId(id);
        }
        if (propertyDisplayDetails != null && this.allowPropertyDisplay(propertyDisplayDetails)) {
            return propertyDisplayDetails;
        }
        return null;
    };
    /**
     *  This method updates the value for property by looking into allowed values of that display group
     *  or from the property instance
     *  
     *  @param propertyInputDetails
     *  @param displayGroup
     *  @param propertyValue
     */
    prototype.buildPropertyDisplayValue = function(propertyValue, propertyInputDetails, displayGroup) {
        displayGroup.setValue(propertyValue);
        if (propertyInputDetails.getAllowedValues() != null && !propertyInputDetails.getAllowedValues().isEmpty()) {
            var propertyAllowedValue = propertyInputDetails.getAllowedValues().iterator();
             while (propertyAllowedValue.hasNext()){
                var propertyInputDetailsValue = propertyAllowedValue.next();
                if (propertyInputDetailsValue != null && propertyInputDetailsValue.getValue() != null && propertyValue.equals(propertyInputDetailsValue.getValue())) {
                    displayGroup.setValue(propertyInputDetailsValue.getName());
                    break;
                }
            }
        }
    };
    /**
     *  This method will update the DisplayDetails map to get Display Details with and without control ID
     *  
     *  @param cfgDisplayDetails
     *  @param controlId
     */
    prototype.updateDisplayDetailsMap = function(cfgDisplayDetails, controlId) {
        if (cfgDisplayDetails != null) {
            var pddIt = cfgDisplayDetails.getProductDisplays().iterator();
             while (pddIt.hasNext()){
                var displayDetails = pddIt.next();
                var displays = displayDetails.getDisplays();
                if (displays.isEmpty()) {
                    if (displayDetails.getControlId() == null || displayDetails.getControlId() == 0) {
                        cfgDisplayDetails.addProductDisplayMap(displayDetails);
                    } else if (displayDetails.getControlId() == controlId) {
                        cfgDisplayDetails.addControlIdProductDisplayMap(displayDetails);
                    }
                } else {
                    var it = displays.iterator();
                     while (it.hasNext()){
                        var display = it.next();
                        if (!display.getControlIds().isEmpty()) {
                            if (display.getControlIds().contains(controlId)) {
                                this.copyElementDisplay(controlId, display, displayDetails);
                                cfgDisplayDetails.addControlIdProductDisplayMap(displayDetails);
                                break;
                            }
                        } else {
                            this.copyElementDisplay(controlId, display, displayDetails);
                            cfgDisplayDetails.addProductDisplayMap(displayDetails);
                        }
                    }
                }
            }
            var pidIt = cfgDisplayDetails.getPropertyDisplays().iterator();
             while (pidIt.hasNext()){
                var propertyInputDetails = pidIt.next();
                var displays = propertyInputDetails.getDisplays();
                if (displays.isEmpty()) {
                    if (propertyInputDetails.getControlId() == null || propertyInputDetails.getControlId() == 0) {
                        cfgDisplayDetails.addPropertyDisplayMap(propertyInputDetails);
                    } else if (propertyInputDetails.getControlId() == controlId) {
                        cfgDisplayDetails.addControlIdPropertyDisplayMap(propertyInputDetails);
                    }
                } else {
                    var it = displays.iterator();
                     while (it.hasNext()){
                        var display = it.next();
                        if (!display.getControlIds().isEmpty()) {
                            if (display.getControlIds().contains(controlId)) {
                                this.copyPropertyDisplay(controlId, display, propertyInputDetails);
                                cfgDisplayDetails.addControlIdPropertyDisplayMap(propertyInputDetails);
                                break;
                            }
                        } else {
                            this.copyPropertyDisplay(controlId, display, propertyInputDetails);
                            cfgDisplayDetails.addPropertyDisplayMap(propertyInputDetails);
                        }
                    }
                }
            }
        }
    };
    prototype.copyElementDisplay = function(controlId, display, dd) {
        dd.setControlId(controlId);
        dd.setParentId(display.getParentId());
        dd.setName(display.getName());
        dd.setDisplayHints(display.getDisplayHints());
    };
    prototype.copyPropertyDisplay = function(controlId, display, dd) {
        dd.setControlId(controlId);
        dd.setParentId(display.getParentId());
        dd.setName(display.getName());
        dd.setDisplayHints(display.getDisplayHints());
        dd.setAllowedValues(display.getAllowedValues());
    };
    /**
     *  This method will retrieve the Display Details for Feature, choice, Properties and exceptions from
     *  Product Displays created for control Id and no control Id
     *  
     *  @param id
     *  @param controlId
     *  @param cfgDisplayDetails
     *  @return
     */
    prototype.retrieveProductDisplayDetails = function(id, controlId, cfgDisplayDetails) {
        var displayDetails = cfgDisplayDetails.getProductDisplayByControlRefId(id);
        if (displayDetails == null) {
            displayDetails = cfgDisplayDetails.getProductDisplayByRefId(id);
        }
        if (displayDetails != null && this.allowProductDisplay(displayDetails)) {
            return displayDetails;
        }
        return null;
    };
    /**
     *  This method will return display group after checking Display hints for property display group
     *  
     *  @param productDisplayDetails
     *  @param cdg
     *  @return
     */
    prototype.allowPropertyDisplay = function(propertyInputDetails) {
        if ((propertyInputDetails.getDisplayHints() == null) || (propertyInputDetails.getDisplayHints() != null && propertyInputDetails.getDisplayHints().isEmpty())) {
            return true;
        } else {
            var dhIt = propertyInputDetails.getDisplayHints().iterator();
             while (dhIt.hasNext()){
                var dh = dhIt.next();
                if (dh.getName().equals("DISPLAY")) {
                    if (dh.getValue().equals("YES")) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    };
    /**
     *  This method assigns the child groups to parent display groups
     *  
     *  @param parentId
     *  @param controlId
     *  @param childDisplayGroup
     *  @param displayGroups
     *  @param displayGroupMap
     *  @param cfgProductDisplays
     */
    prototype.assignParentDisplayGroup = function(parentId, controlId, childDisplayGroup, displayGroups, displayGroupMap, cfgProductDisplays) {
        if (parentId != null && parentId != 0) {
            var parentDisplayGroup = displayGroupMap.get(parentId);
            if (parentDisplayGroup == null) {
                var parentDisplayDetails = this.retrieveProductDisplayDetails(parentId, controlId, cfgProductDisplays);
                if (parentDisplayDetails != null) {
                    parentDisplayGroup = new DisplayGroup();
                    parentDisplayGroup.setId(parentDisplayDetails.getRefId());
                    parentDisplayGroup.setName(parentDisplayDetails.getName());
                    displayGroupMap.put(parentDisplayDetails.getRefId(), parentDisplayGroup);
                    parentDisplayGroup.getDisplayGroups().add(childDisplayGroup);
                    this.assignParentDisplayGroup(parentDisplayDetails.getParentId(), controlId, parentDisplayGroup, displayGroups, displayGroupMap, cfgProductDisplays);
                }
            } else {
                parentDisplayGroup.getDisplayGroups().add(childDisplayGroup);
            }
        } else {
            displayGroups.add(childDisplayGroup);
        }
    };
    /**
     *  This method will return the product display object for the instance of product
     *  
     *  @param product
     *  @param additionalDisplayGroups
     *  @param controlId
     *  @param cfgDisplayDetails
     *  @param childDisplayGroupList
     *  @return
     */
    prototype.buildProductDisplay = function(product, displayGroups, additionalDisplayGroups, cfgDisplayDetails, controlId, skuDisplayGroups) {
        var productdisplay = new ProductDisplay();
        productdisplay.setId(product.getId());
        productdisplay.setQty(product.getQty());
        productdisplay.setUserProductName(product.getUserProductName());
        productdisplay.setVersion(product.getVersion());
        productdisplay.setContentAssociations(product.getContentAssociations());
        productdisplay.setProductionContentAssociations(product.getProductionContentAssociations());
        productdisplay.setInstanceId(product.getInstanceId());
        productdisplay.setName(this.getProductDisplayName(cfgDisplayDetails, product, controlId));
        productdisplay.setDisplayGroups(displayGroups);
        productdisplay.setAdditionaldisplayGroups(additionalDisplayGroups);
        productdisplay.setSkuDisplayGroups(skuDisplayGroups);
        productdisplay.setPrintPageCount(this.countPrintablePages(product));
        return productdisplay;
    };
    /**
     *  This method return Product display name for product instance
     *  
     *  @param cfgDisplayDetails
     *  @param product
     *  @param controlId
     *  @return
     */
    prototype.getProductDisplayName = function(cfgDisplayDetails, product, controlId) {
        var productDisplayDetails = this.retrieveProductDisplayDetails(product.getId(), controlId, cfgDisplayDetails);
        if (productDisplayDetails != null) {
            return productDisplayDetails.getName();
        }
        return product.getName();
    };
    /**
     *  This method will return display group after checking Display hints
     *  
     *  @param productDisplayDetails
     *  @param cdg
     *  @return
     */
    prototype.allowProductDisplay = function(productDisplayDetails) {
        if ((productDisplayDetails.getDisplayHints() == null) || (productDisplayDetails.getDisplayHints() != null && productDisplayDetails.getDisplayHints().isEmpty())) {
            return true;
        } else {
            var dhIt = productDisplayDetails.getDisplayHints().iterator();
             while (dhIt.hasNext()){
                var dh = dhIt.next();
                if (dh.getName().equals("DISPLAY")) {
                    if (dh.getValue().equals("YES")) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    };
    prototype.filterEmptyDisplayGroups = function(displayGroups) {
        var it = displayGroups.iterator();
        var validDsplGrps = new ArrayList();
         while (it.hasNext()){
            var displayGroup = it.next();
            this.filterEmptyDisplayGroups(displayGroup.getDisplayGroups());
            if (displayGroup.getValue() == null) {
                if (!displayGroup.getDisplayGroups().isEmpty()) {
                    validDsplGrps.add(displayGroup);
                }
            } else {
                validDsplGrps.add(displayGroup);
            }
        }
        if (!validDsplGrps.isEmpty()) {
            displayGroups.clear();
            displayGroups.addAll(validDsplGrps);
        } else {
            displayGroups.clear();
        }
        return displayGroups;
    };
    prototype.buildSkuDisplayGroups = function(productInstance, controlId, productData) {
        var skuDisplayGroups = new ArrayList();
        if (productData.getDisplays() != null) {
            var skuDdIt = productData.getDisplays().getSkuDisplays().iterator();
             while (skuDdIt.hasNext()){
                var skuDd = skuDdIt.next();
                var edIt = skuDd.getDisplays().iterator();
                 while (edIt.hasNext()){
                    var ed = edIt.next();
                    if (ed.getControlIds().contains(controlId) && productInstance.getExternalSkus() != null && !productInstance.getExternalSkus().isEmpty()) {
                        var skuDisplayGroup = new SkuDisplayGroups();
                        skuDisplayGroup.setId(skuDd.getId());
                        skuDisplayGroup.setName(ed.getName());
                        skuDisplayGroup.setSkus(productInstance.getExternalSkus());
                        skuDisplayGroups.add(skuDisplayGroup);
                    }
                }
            }
        }
        if (skuDisplayGroups.isEmpty() && productInstance.getExternalSkus() != null && controlId == 3) {
            var skuDisplayGroup = new SkuDisplayGroups();
            skuDisplayGroup.setId((new Date()).getTime());
            skuDisplayGroup.setName("SKU");
            skuDisplayGroup.setSkus(productInstance.getExternalSkus());
            skuDisplayGroups.add(skuDisplayGroup);
        }
        return skuDisplayGroups;
    };
    prototype.countPrintablePages = function(product) {
        var printPageCount = 0;
        if (product.getContentAssociations() != null && !product.getContentAssociations().isEmpty()) {
            for (var i = 0; i < product.getContentAssociations().size(); i++) {
                var ca = product.getContentAssociations().get(i);
                if (ca != null && ca.getPageGroups() != null && !ca.getPageGroups().isEmpty()) {
                    for (var j = 0; j < ca.getPageGroups().size(); j++) {
                        var pageGroup = ca.getPageGroups().get(j);
                        printPageCount += (pageGroup.getEnd() - pageGroup.getStart()) + 1;
                    }
                }
            }
        }
        if (product.getPageExceptions() != null && !product.getPageExceptions().isEmpty()) {
            for (var i = 0; i < product.getPageExceptions().size(); i++) {
                var pageExceptionInstance = product.getPageExceptions().get(i);
                if (pageExceptionInstance != null && pageExceptionInstance.getProperties() != null && !pageExceptionInstance.getProperties().isEmpty()) {
                    var propertyInstanceIt = pageExceptionInstance.getProperties().iterator();
                     while (propertyInstanceIt.hasNext()){
                        var propertyInstance = propertyInstanceIt.next();
                        if (propertyInstance != null && propertyInstance.getValue().equals("TAB")) {
                            for (var k = 0; k < pageExceptionInstance.getRanges().size(); k++) {
                                var pageRange = pageExceptionInstance.getRanges().get(k);
                                printPageCount += (pageRange.getEnd() - pageRange.getStart()) + 1;
                            }
                        }
                    }
                }
            }
        }
        if (product.getProductionContentAssociations() != null && !product.getProductionContentAssociations().isEmpty()) {
            for (var i = 0; i < product.getProductionContentAssociations().size(); i++) {
                var pca = product.getProductionContentAssociations().get(i);
                if (pca != null && pca.getPurpose() != null && pca.getPurpose().equals(ContentPurpose.SPINE)) {
                    printPageCount += 1;
                }
            }
        }
        return printPageCount;
    };
}, {});

var ConfiguredProduct = function() {
    Product.call(this);
};
stjs.extend(ConfiguredProduct, Product, [], null, {contentRequirements: {name: "Set", arguments: ["ContentRequirement"]}, externalRequirements: "ExternalRequirements", features: {name: "Set", arguments: ["F"]}, properties: {name: "Set", arguments: ["P"]}, pageExceptions: {name: "List", arguments: ["Pe"]}});

var AbstractProductData = function() {
    this.presets = new ArraySet();
    this.designTemplates = new ArrayList();
};
stjs.extend(AbstractProductData, null, [], function(constructor, prototype) {
    prototype.product = null;
    prototype.rules = null;
    prototype.displays = null;
    prototype.presets = null;
    prototype.designTemplates = null;
    prototype.getProduct = function() {
        return this.product;
    };
    prototype.setProduct = function(product) {
        this.product = product;
    };
    prototype.getRules = function() {
        return this.rules;
    };
    prototype.setRules = function(rules) {
        this.rules = rules;
    };
    prototype.getPresets = function() {
        return this.presets;
    };
    prototype.setPresets = function(presets) {
        this.presets = presets;
    };
    prototype.getDisplays = function() {
        return this.displays;
    };
    prototype.setDisplays = function(displays) {
        this.displays = displays;
    };
    prototype.getDesignTemplates = function() {
        return this.designTemplates;
    };
    prototype.setDesignTemplates = function(designTemplates) {
        this.designTemplates = designTemplates;
    };
}, {product: "Product", rules: "ProductRules", displays: "ProductDisplays", presets: {name: "Set", arguments: ["Preset"]}, designTemplates: {name: "List", arguments: ["DesignTemplate"]}});

var DefaultProductContext = function() {
    this.contentAdded = false;
    this.selectedChoiceIds = new ArraySet();
    this.defaultOverrideChoiceIds = new ArraySet();
    this.contextMap = new ArrayMap();
    this.refIds = new ArrayList();
};
stjs.extend(DefaultProductContext, null, [ProductContext], function(constructor, prototype) {
    prototype.product = null;
    prototype.contentAdded = null;
    prototype.selectedChoiceIds = null;
    prototype.defaultOverrideChoiceIds = null;
    prototype.contextMap = null;
    prototype.refIds = null;
    prototype.pageException = null;
    prototype.coreProduct = null;
    prototype.getProduct = function() {
        return this.product;
    };
    prototype.setProduct = function(product) {
        this.product = product;
    };
    prototype.isContentAdded = function() {
        return this.contentAdded;
    };
    prototype.setContentAdded = function(contentAdded) {
        this.contentAdded = contentAdded;
    };
    prototype.getSelectedChoiceIds = function() {
        return this.selectedChoiceIds;
    };
    prototype.setSelectedChoiceIds = function(selectedChoiceIds) {
        this.selectedChoiceIds = selectedChoiceIds;
    };
    prototype.getDefaultOverrideChoiceIds = function() {
        return this.defaultOverrideChoiceIds;
    };
    prototype.setDefaultOverrideChoiceIds = function(defaultOverrideChoiceIds) {
        this.defaultOverrideChoiceIds = defaultOverrideChoiceIds;
    };
    prototype.put = function(key, value) {
        this.contextMap.put(key, value);
    };
    prototype.getValue = function(key) {
        return this.contextMap.get(key);
    };
    prototype.setRefIds = function(refIds) {
        this.refIds = refIds;
    };
    prototype.getRefIds = function() {
        return this.refIds;
    };
    prototype.getPageException = function() {
        return this.pageException;
    };
    prototype.setPageException = function(pageException) {
        this.pageException = pageException;
    };
    prototype.getCoreProduct = function() {
        return this.coreProduct;
    };
    prototype.setCoreProduct = function(coreProduct) {
        this.coreProduct = coreProduct;
    };
}, {product: "ProductInstance", selectedChoiceIds: {name: "Set", arguments: [null]}, defaultOverrideChoiceIds: {name: "Set", arguments: [null]}, contextMap: {name: "Map", arguments: [null, "Object"]}, refIds: {name: "List", arguments: [null]}, pageException: "PageExceptionInstance", coreProduct: "Product"});

var ProductHint = function() {
    this.validateProductConfig = true;
};
stjs.extend(ProductHint, null, [], function(constructor, prototype) {
    prototype.productId = null;
    prototype.version = 0;
    prototype.controlId = null;
    prototype.instanceId = null;
    prototype.presetId = null;
    prototype.qty = 0;
    prototype.defaultContent = false;
    prototype.validateProductConfig = false;
    prototype.choiceIds = null;
    prototype.contentHints = null;
    prototype.sourceProduct = null;
    prototype.templateId = null;
    prototype.templateVendorCode = null;
    prototype.getProductId = function() {
        return this.productId;
    };
    prototype.setProductId = function(productId) {
        this.productId = productId;
    };
    prototype.getVersion = function() {
        return this.version;
    };
    prototype.setVersion = function(version) {
        this.version = version;
    };
    prototype.getControlId = function() {
        return this.controlId;
    };
    prototype.setControlId = function(controlId) {
        this.controlId = controlId;
    };
    prototype.getInstanceId = function() {
        return this.instanceId;
    };
    prototype.setInstanceId = function(instanceId) {
        this.instanceId = instanceId;
    };
    prototype.getPresetId = function() {
        return this.presetId;
    };
    prototype.setPresetId = function(presetId) {
        this.presetId = presetId;
    };
    prototype.getQty = function() {
        return this.qty;
    };
    prototype.setQty = function(qty) {
        this.qty = qty;
    };
    prototype.isDefaultContent = function() {
        return this.defaultContent;
    };
    prototype.setDefaultContent = function(defaultContent) {
        this.defaultContent = defaultContent;
    };
    prototype.isValidateProductConfig = function() {
        return this.validateProductConfig;
    };
    prototype.setValidateProductConfig = function(validateProductConfig) {
        this.validateProductConfig = validateProductConfig;
    };
    prototype.getContentHints = function() {
        return this.contentHints;
    };
    prototype.setContentHints = function(contentHints) {
        this.contentHints = contentHints;
    };
    prototype.getChoiceIds = function() {
        return this.choiceIds;
    };
    prototype.setChoiceIds = function(choiceIds) {
        this.choiceIds = choiceIds;
    };
    prototype.getSourceProduct = function() {
        return this.sourceProduct;
    };
    prototype.setSourceProduct = function(sourceProduct) {
        this.sourceProduct = sourceProduct;
    };
    prototype.getTemplateId = function() {
        return this.templateId;
    };
    prototype.setTemplateId = function(templateId) {
        this.templateId = templateId;
    };
    prototype.getTemplateVendorCode = function() {
        return this.templateVendorCode;
    };
    prototype.setTemplateVendorCode = function(templateVendorCode) {
        this.templateVendorCode = templateVendorCode;
    };
}, {choiceIds: {name: "List", arguments: [null]}, contentHints: {name: "List", arguments: ["ContentHint"]}, sourceProduct: "ProductInstance"});

var ProductConfigurationProcessor = function() {};
stjs.extend(ProductConfigurationProcessor, null, [], function(constructor, prototype) {
    prototype.getConfiguredProduct = function(product, productRules, context) {
        var configProduct = null;
        var configFeature = null;
        var configuredCr = null;
        var configuredPe = null;
        configProduct = new ConfiguredProduct();
        configProduct.setId(product.getId());
        configProduct.setName(product.getName());
        configProduct.setPriceable(true);
        configProduct.setExternalRequirements(product.getExternalRequirements());
        var choiceMap = this.buildChoiceMap(product);
        var cgInstanceMap = this.buildCompatabilityGroups(context, choiceMap);
        var configuredFeatures = new ArraySet();
        var it = product.getFeatures().iterator();
        var overrideFlagRules = new ArrayMap();
        var overrideFeatures = new ArrayList();
         while (it.hasNext()){
            configFeature = this.getConfiguredFeature(it.next(), productRules, context, cgInstanceMap, product);
            configuredFeatures.add(configFeature);
            try {
                var overrideRule = productRules.getSingleRuleDef(configFeature.getId(), RuleType.DEFAULT_OVERRIDE);
                if (overrideRule != null) {
                    this.applyDefaultChoiceOverride(configFeature, overrideRule, context);
                }
            }catch (e) {
                 throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
            }
            overrideFeatures = this.buildOverrideFlagRulesList(configFeature, productRules, overrideFlagRules, overrideFeatures);
        }
        this.applyDefaultOverrides(overrideFlagRules, overrideFeatures, context, productRules);
        configProduct.setFeatures(configuredFeatures);
        var configuredProperties = new ArraySet();
        var propertyIt = product.getProperties().iterator();
        var productProperty = null;
         while (propertyIt.hasNext()){
            productProperty = propertyIt.next();
            configuredProperties.add(this.getConfiguredProperty(productProperty, productRules, context));
        }
        configProduct.setProperties(configuredProperties);
        var configuredCrs = new ArraySet();
        var crIt = product.getContentRequirements().iterator();
         while (crIt.hasNext()){
            configuredCr = this.getConfiguredContentRequirement(crIt.next(), productRules, context);
            if (configuredCr != null) {
                configuredCrs.add(configuredCr);
            }
        }
        configProduct.setContentRequirements(configuredCrs);
        var configuredPes = new ArrayList();
        var peIt = product.getPageExceptions().iterator();
         while (peIt.hasNext()){
            configuredPe = this.getConfiguredPageException(peIt.next(), productRules, context, configProduct.getFeatures());
            configuredPes.add(configuredPe);
        }
        configProduct.setPageExceptions(configuredPes);
        this.applyProductOverrides(configProduct, productRules, context);
        return configProduct;
    };
    prototype.buildOverrideFlagRulesList = function(feature, productRules, overrideFlagRules, overrideFeatures) {
        var rule;
        var itFeature;
        var newRuleAdded = false;
        var it = null;
        var newOverrideFeatures = new ArrayList();
        try {
            rule = productRules.getSingleRuleDef(feature.getId(), RuleType.OVERRIDE_DEFAULT_FLAG);
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
        if (rule != null) {
            it = overrideFeatures.iterator();
             while (it.hasNext()){
                itFeature = it.next();
                if ((overrideFlagRules.get(itFeature.getId()).getSequence() > rule.getSequence()) && !newRuleAdded) {
                    newOverrideFeatures.add(feature);
                    overrideFlagRules.put(feature.getId(), rule);
                    newRuleAdded = true;
                }
                newOverrideFeatures.add(itFeature);
            }
            if (!newRuleAdded) {
                newOverrideFeatures.add(feature);
                overrideFlagRules.put(feature.getId(), rule);
            }
            return newOverrideFeatures;
        }
        return overrideFeatures;
    };
    prototype.applyDefaultOverrides = function(overrideFlagRules, overrideFeatures, context, rules) {
        var f = null;
        var r = null;
        var it = overrideFeatures.iterator();
        try {
             while (it.hasNext()){
                f = it.next();
                r = overrideFlagRules.get(f.getId());
                f.setOverrideWithDefault(r.evaluate(context));
                if (f.isOverrideWithDefault()) {
                    this.applyDefaultOverride(f, rules, context);
                }
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    prototype.getConfiguredFeature = function(feature, productRules, context, cgInstanceMap, product) {
        var configFeature = null;
        var configuredCr = null;
        configFeature = new ConfiguredFeature();
        configFeature.setId(feature.getId());
        configFeature.setName(feature.getName());
        configFeature.setDefaultChoiceId(feature.getDefaultChoiceId());
        configFeature.setChoiceRequired(feature.isChoiceRequired());
        configFeature.setOverrideWithDefault(feature.isOverrideWithDefault());
        var configuredChoices = new ArraySet();
        var it = feature.getChoices().iterator();
        var choice = null;
         while (it.hasNext()){
            choice = it.next();
            configuredChoices.add(this.getConfiguredChoice(choice, productRules, context, cgInstanceMap));
        }
        configFeature.setChoices(configuredChoices);
        this.applyFeatureOverrides(configFeature, productRules, context);
        return configFeature;
    };
    prototype.getConfiguredChoice = function(choice, productRules, context, cgInstanceMap) {
        var configChoice = new ConfiguredChoice();
        configChoice.setId(choice.getId());
        configChoice.setName(choice.getName());
        var configuredProperties = new ArraySet();
        var it = choice.getProperties().iterator();
        var property = null;
         while (it.hasNext()){
            property = it.next();
            configuredProperties.add(this.getConfiguredProperty(property, productRules, context));
        }
        configChoice.setProperties(configuredProperties);
        this.applyChoiceOverrides(configChoice, productRules, context, cgInstanceMap, choice);
        return configChoice;
    };
    prototype.getConfiguredProperty = function(property, productRules, context) {
        var configProperty = new ConfiguredProperty();
        configProperty.setId(property.getId());
        configProperty.setName(property.getName());
        configProperty.setRequired(property.isRequired());
        configProperty.setValue(property.getValue());
        configProperty.setInputAllowed(property.isInputAllowed());
        var configuredBounds = new ArraySet();
        var boundIt = property.getBounds().iterator();
        var bound = null;
         while (boundIt.hasNext()){
            bound = boundIt.next();
            configuredBounds.add(this.getConfiguredBound(bound, productRules, context));
        }
        configProperty.setBounds(configuredBounds);
        this.applyPropertyOverrides(configProperty, productRules, context);
        return configProperty;
    };
    prototype.getConfiguredBound = function(bound, productRules, context) {
        var configBound = new Bound();
        configBound.setId(bound.getId());
        configBound.setName(bound.getName());
        configBound.setMax(bound.getMax());
        configBound.setMin(bound.getMin());
        configBound.setType(bound.getType());
        configBound.setExpression(bound.getExpression());
        var allowedValuesIt = null;
        if (bound.getAllowedValues() != null) {
            var allowedValues = new ArraySet();
            allowedValuesIt = bound.getAllowedValues().iterator();
            bound.getAllowedValues().iterator();
            var propertyAllowedValues = null;
             while (allowedValuesIt.hasNext()){
                propertyAllowedValues = allowedValuesIt.next();
                var configPropAllowedValues = this.getConfiguredAllowedValues(propertyAllowedValues, productRules, context);
                if (configPropAllowedValues != null) {
                    allowedValues.add(configPropAllowedValues);
                }
            }
            configBound.setAllowedValues(allowedValues);
        } else {
            configBound.setAllowedValues(null);
        }
        return configBound;
    };
    prototype.getConfiguredContentRequirement = function(cr, productRules, context) {
        try {
            if (this.isEntitySelectable(cr.getId(), productRules, context, true)) {
                return cr.clone();
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
        return null;
    };
    /**
     *  Apply any override rules to the specified ConfiguredProduct
     *  @param product
     *  @param context
     *  @throws ProductConfigProcessorException
     */
    prototype.applyProductOverrides = function(product, productRules, context) {
        try {
            context.getProduct().setPriceable(this.isProductPriceable(product, productRules, context));
            context.getProduct().setProofRequired(this.isProofRequired(product, productRules, context));
            context.getProduct().setIsOutSourced(this.getIsOutSourced(product, productRules, context));
            if (productRules.getSingleRuleDef(product.getId(), RuleType.PRODUCT_QTY) != null) {
                context.getProduct().setQty(stjs.trunc(this.applyOverrideProductQty(product, productRules, context)));
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    /**
     *  Apply any override rules to the specified ConfiguredFeature
     *  @param feature
     *  @param context
     *  @throws ProductConfigProcessorException
     */
    prototype.applyFeatureOverrides = function(feature, productRules, context) {
        try {
            feature.setSelectable(this.isEntitySelectable(feature.getId(), productRules, context, true));
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    /**
     *  Apply any override rules to the specified ConfiguredChoice
     *  @param choice
     *  @param context
     *  @throws ProductConfigProcessorException
     */
    prototype.applyChoiceOverrides = function(cfgC, productRules, context, cgInstanceMap, choice) {
        try {
            var isCompatible = true;
            var cgBaseSet = choice.getCompatibilityGroups();
            if (cgBaseSet != null && cgInstanceMap != null) {
                isCompatible = this.isChoiceCompatible(cgBaseSet, cgInstanceMap);
            }
            cfgC.setSelectable(this.isEntitySelectable(cfgC.getId(), productRules, context, isCompatible));
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    /**
     *  Apply any override rules to the specified Property
     *  @param property
     *  @param productRules
     *  @param context
     *  @throws ProductConfigProcessorException
     *  @throws RuleConfigurationException
     */
    prototype.applyPropertyOverrides = function(property, productRules, context) {
        try {
            var newValue = property.getValue();
            var rule = productRules.getSingleRuleDef(property.getId(), RuleType.VALUE);
            if (rule != null) {
                newValue = rule.evaluateType(context, ValueType.STRING);
                property.setOverrideWithConfiguredValue(true);
            } else {
                property.setOverrideWithConfiguredValue(false);
            }
            property.setValue(newValue);
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    /**
     *  Returns a new override value if a Value rule exists for the specified entity id
     *  @param entityId
     *  @param currentValue if no rule is found, this value will be returned
     *  @param valueType ValueType type of value to be returned
     *  @param productRules
     *  @param context
     *  @return override value or currentValue
     *  @throws RuleConfigurationException
     */
    prototype.getValueOverride = function(entityId, currentValue, valueType, productRules, context) {
        var newValue = currentValue;
        var rule = productRules.getSingleRuleDef(entityId, RuleType.VALUE);
        if (rule != null) {
            newValue = rule.evaluateType(context, valueType);
        }
        return newValue;
    };
    /**
     *  Returns true if the specified entity is selectable based on selectability rules.
     *  If no rule is found, the entity is selectable
     *  @param entityId
     *  @param context
     *  @return
     *  @throws RuleConfigurationException
     *  @throws ProductConfigProcessorException
     */
    prototype.isEntitySelectable = function(entityId, productRules, context, isSelectable) {
        var rule = productRules.getSingleRuleDef(entityId, RuleType.AVAILABLE);
        if (rule != null) 
            isSelectable = rule.evaluate(context);
        return isSelectable;
    };
    prototype.applyDefaultOverride = function(feature, productRules, context) {
        try {
            var newValue = null;
            var rule = productRules.getSingleRuleDef(feature.getId(), RuleType.OVERRIDE_DEFAULT);
            if (rule != null) {
                newValue = rule.evaluateType(context, ValueType.LONG);
            }
            if (newValue != null) {
                feature.setDefaultChoiceId(newValue);
                context.getDefaultOverrideChoiceIds().add(feature.getDefaultChoiceId());
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    prototype.applyDefaultChoiceOverride = function(feature, rule, context) {
        try {
            var newValue = null;
            if (rule != null) {
                newValue = rule.evaluateType(context, ValueType.LONG);
            }
            if (newValue != null) {
                feature.setDefaultChoiceId(newValue);
                feature.setOverrideWithDefault(true);
            } else {
                feature.setOverrideWithDefault(false);
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
    };
    prototype.reconfigureProduct = function(product, productRules, context) {
        var configProduct = this.getConfiguredProduct(product, productRules, context);
        this.applyConfiguredProductToInstance(configProduct, context.getProduct(), true);
        if (!context.getDefaultOverrideChoiceIds().isEmpty()) {
            context.getSelectedChoiceIds().clear();
            context.getSelectedChoiceIds().addAll(context.getDefaultOverrideChoiceIds());
            context.getDefaultOverrideChoiceIds().clear();
            configProduct = this.getConfiguredProduct(product, productRules, context);
            this.applyConfiguredProductToInstance(configProduct, context.getProduct(), false);
        }
        return configProduct;
    };
    prototype.getValidationResultsForProduct = function(configuredProduct, productRules, context) {
        var results = new ArrayList();
        this.addRuleValidationResults(productRules, context, results);
        this.addValidationResultsFromInstance(context, productRules, configuredProduct, results);
        this.addValidationResultsFromConfigured(configuredProduct, context.getProduct(), results);
        this.addContentValidationResults(configuredProduct, productRules, context, results);
        return results;
    };
    prototype.addValidationResultsFromConfigured = function(configuredProduct, pi, results) {
        var fi = null;
        var ci = null;
        var prpti = null;
        var cfgF = null;
        var cfgC = null;
        var cfgPrpt = null;
        var cfgFit = configuredProduct.getFeatures().iterator();
        var pit = null;
        var resultMap = new ArrayMap();
         while (cfgFit.hasNext()){
            cfgF = cfgFit.next();
            fi = pi.getFeatureById(cfgF.getId());
            if (fi == null) {
                if (cfgF.isChoiceRequired()) {
                    results.add(this.createValidationResult(ValidationResultCode.FEATURE_REQUIRED, cfgF.getId(), ElementType.PRODUCT, 0));
                }
            } else {
                if (fi.getChoice() == null) {
                    if (cfgF.isChoiceRequired()) {
                        results.add(this.createValidationResult(ValidationResultCode.CHOICE_REQUIRED, cfgF.getId(), ElementType.PRODUCT, 0));
                    }
                } else {
                    ci = fi.getChoice();
                    cfgC = Utils.getElementById(ci.getId(), cfgF.getChoices());
                    pit = cfgC.getProperties().iterator();
                     while (pit.hasNext()){
                        cfgPrpt = pit.next();
                        prpti = Utils.getElementById(cfgPrpt.getId(), ci.getProperties());
                        if (prpti == null) {
                            if (cfgPrpt.isRequired()) {
                                results.add(this.createValidationResult(ValidationResultCode.PROPERTY_REQUIRED, cfgPrpt.getId(), ElementType.PRODUCT, 0));
                            }
                        }
                    }
                }
            }
        }
        if (configuredProduct.getExternalRequirements() != null) {
            if (configuredProduct.getExternalRequirements().isProductionTimeRequired()) {
                this.validateProductionTime(pi.getExternalProductionDetails(), results);
            }
            if (configuredProduct.getExternalRequirements().isWeightRequired()) {
                this.validateWeight(pi.getExternalProductionDetails(), results);
            }
        }
        results.addAll(resultMap.values());
    };
    prototype.validateProductionTime = function(externalProductionDetails, results) {
        if (externalProductionDetails == null || externalProductionDetails.getProductionTime() == null || Double.isNaN(externalProductionDetails.getProductionTime().getValue())) {
            results.add(this.createValidationResult(ValidationResultCode.PRODUCTION_TIME_REQUIRED, null, ElementType.PRODUCT, 0));
            return;
        }
        var productionTime = externalProductionDetails.getProductionTime().getValue();
        if (productionTime != null) {
            var numberStr = productionTime.toString();
            var regex = "^\\d{1,4}(\\.\\d)?$";
            if (!numberStr.matches(regex)) {
                results.add(this.createValidationResult(ValidationResultCode.PRODUCTION_TIME_INVALID, null, ElementType.PRODUCT, 0));
            }
        }
    };
    prototype.validateWeight = function(externalProductionDetails, results) {
        if (externalProductionDetails == null || externalProductionDetails.getWeight() == null || externalProductionDetails.getWeight().getValue() == null) {
            results.add(this.createValidationResult(ValidationResultCode.PRODUCTION_WEIGHT_REQUIRED, null, ElementType.PRODUCT, 0));
            return;
        }
        if (externalProductionDetails.getWeight().getValue() != null) {
            var numberStr = externalProductionDetails.getWeight().getValue().toString();
            var regex = "^\\d{1,4}(\\.\\d)?$";
            if (!numberStr.matches(regex)) {
                results.add(this.createValidationResult(ValidationResultCode.WEIGHT_INVALID, null, ElementType.PRODUCT, 0));
            }
        }
    };
    prototype.addValidationResultsFromInstance = function(context, productRules, configuredProduct, results) {
        var pi = context.getProduct();
        var fit = pi.getFeatures().iterator();
        var pit = pi.getProperties().iterator();
         while (pit.hasNext()){
            this.addValidationResultsFromPrptInstance(pit.next(), configuredProduct.getProperties(), results, ElementType.PRODUCT, 0);
        }
         while (fit.hasNext()){
            this.addValidationResultsFromFeatureInstance(fit.next(), configuredProduct.getFeatures(), results, ElementType.PRODUCT, 0);
        }
        this.addValidationResultsFromPageExceptions(pi, configuredProduct.getPageExceptions(), results);
    };
    prototype.addValidationResultsFromFeatureInstance = function(fi, cfgFeatures, results, elementType, index) {
        var element = Utils.getElementById(fi.getId(), cfgFeatures);
        if (element == null) {
            results.add(this.createValidationResult(ValidationResultCode.FEATURE_NOT_SELECTABLE, fi.getId(), elementType, index));
        } else {
            var configuredFeature = element;
            if (!configuredFeature.isSelectable()) {
                results.add(this.createValidationResult(ValidationResultCode.FEATURE_NOT_SELECTABLE, fi.getId(), elementType, index));
            } else {
                if (fi.getChoice() != null) {
                    this.addValidationResultsFromChoiceInstance(fi.getChoice(), configuredFeature.getChoices(), results, elementType, index);
                }
            }
        }
    };
    prototype.addValidationResultsFromChoiceInstance = function(ci, cfgChoices, results, elementType, index) {
        var pit = null;
        var element = Utils.getElementById(ci.getId(), cfgChoices);
        if (element == null) {
            results.add(this.createValidationResult(ValidationResultCode.CHOICE_NOT_SELECTABLE, ci.getId(), elementType, index));
        } else {
            var configuredChoice = element;
            if (!configuredChoice.isSelectable()) {
                results.add(this.createValidationResult(ValidationResultCode.CHOICE_NOT_SELECTABLE, ci.getId(), elementType, index));
            } else {
                pit = ci.getProperties().iterator();
                 while (pit.hasNext()){
                    this.addValidationResultsFromPrptInstance(pit.next(), configuredChoice.getProperties(), results, elementType, index);
                }
            }
        }
    };
    prototype.addValidationResultsFromPrptInstance = function(prpti, cfgProperties, results, elementType, index) {
        var element = Utils.getElementById(prpti.getId(), cfgProperties);
        if (element == null) {
            results.add(this.createValidationResult(ValidationResultCode.PROPERTY_NOT_ALLOWED, prpti.getId(), elementType, index));
        } else {
            var cfgPrpt = element;
            if (cfgPrpt.isInputAllowed()) {
                if (cfgPrpt.isRequired() && prpti.getValue() == null) {
                    results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_REQUIRED, prpti.getId(), elementType, index));
                } else if (prpti.getValue() != null) {
                    this.validateBounds(prpti, cfgPrpt, results);
                }
            }
        }
    };
    prototype.addValidationResultsFromPageExceptions = function(pi, cfgPges, results) {
        var index = 0;
        var pgei = null;
        var fit = null;
        var prit = null;
        var range = null;
        var it = pi.getContentAssociations().iterator();
        var pageGroupIt = null;
        var pageGroup = null;
        var pageCount = 0;
         while (it.hasNext()){
            pageGroupIt = it.next().getPageGroups().iterator();
             while (pageGroupIt.hasNext()){
                pageGroup = pageGroupIt.next();
                pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart() + 1);
            }
        }
        var peit = pi.getPageExceptions().iterator();
         while (peit.hasNext()){
            pgei = peit.next();
            var pePropIt = pgei.getProperties().iterator();
             while (pePropIt.hasNext()){
                var peProp = pePropIt.next();
                if (Utils.isStringsEqual(peProp.getValue(), "TAB") || Utils.isStringsEqual(peProp.getValue(), "INSERT")) {
                    prit = pgei.getRanges().iterator();
                     while (prit.hasNext()){
                        range = prit.next();
                        pageCount = pageCount + (range.getEnd() - range.getStart() + 1);
                    }
                }
            }
        }
        peit = pi.getPageExceptions().iterator();
         while (peit.hasNext()){
            pgei = peit.next();
            var cfgPge = this.getPageExceptionById(pgei.getId(), cfgPges);
            if (cfgPge == null || !cfgPge.isSelectable()) {
                var vr = this.createValidationResult(ValidationResultCode.EXCEPTION_NOT_SELECTABLE, pgei.getId(), ElementType.PAGEEXCEPTION, index);
                vr.setElementInstanceId(pgei.getInstanceId());
                results.add(vr);
            } else {
                if (pgei.getRanges().isEmpty()) {
                    var vr = this.createValidationResult(ValidationResultCode.EXCEPTION_PAGE_RANGE_REQUIRED, null, ElementType.PAGEEXCEPTION, index);
                    vr.setElementInstanceId(pgei.getInstanceId());
                    results.add(vr);
                } else {
                    prit = pgei.getRanges().iterator();
                     while (prit.hasNext()){
                        range = prit.next();
                        if (range.getStart() > range.getEnd() || range.getStart() > pageCount) {
                            var vr = this.createValidationResult(ValidationResultCode.EXCEPTION_START_PAGE_INVALID, null, ElementType.PAGEEXCEPTION, index);
                            vr.setElementInstanceId(pgei.getInstanceId());
                            results.add(vr);
                        }
                        if (range.getEnd() < range.getStart() || range.getEnd() > pageCount) {
                            var vr = this.createValidationResult(ValidationResultCode.EXCEPTION_END_PAGE_INVALID, null, ElementType.PAGEEXCEPTION, index);
                            vr.setElementInstanceId(pgei.getInstanceId());
                            results.add(vr);
                        }
                        var compareToPeIt = pi.getPageExceptions().iterator();
                        var comparetoIndex = 0;
                         while (compareToPeIt.hasNext()){
                            var hasInvalidRange = false;
                            var compareToPe = compareToPeIt.next();
                            if (comparetoIndex > index && !hasInvalidRange) {
                                var compareToRIt = compareToPe.getRanges().iterator();
                                 while (compareToRIt.hasNext()){
                                    var compareToRange = compareToRIt.next();
                                    if (range.getStart() <= compareToRange.getEnd() && range.getEnd() >= compareToRange.getStart()) {
                                        var vr = this.createValidationResult(ValidationResultCode.PAGE_EXCEPTION_PAGE_RANGE_INVALID, null, ElementType.PAGEEXCEPTION, index);
                                        vr.setElementInstanceId(pgei.getInstanceId());
                                        results.add(vr);
                                        hasInvalidRange = true;
                                    }
                                }
                            }
                            comparetoIndex++;
                        }
                    }
                }
            }
            index = index + 1;
        }
    };
    prototype.getPageExceptionById = function(id, pges) {
        var pge = null;
        var it = pges.iterator();
         while (it.hasNext()){
            pge = it.next();
            if (pge.getId().equals(id)) {
                return pge;
            }
        }
        return null;
    };
    prototype.createValidationResult = function(resultCode, refId, elementType, index) {
        var vr = new ValidationResult();
        this.setValidationResultAttributes(vr, resultCode, refId, elementType, index);
        return vr;
    };
    prototype.createContentValidationResult = function(resultCode, elementType, index, contentReference, parentContentReference, purpose, pageGroups) {
        var vr = new ContentValidationResult();
        this.setValidationResultAttributes(vr, resultCode, null, elementType, index);
        if (contentReference != null) {
            vr.setContentReference(contentReference);
        }
        if (parentContentReference != null) {
            vr.setParentContentReference(parentContentReference);
        }
        if (pageGroups != null) {
            vr.setPageGroups(pageGroups);
        }
        if (purpose != null) {
            vr.setPurpose(purpose);
        }
        return vr;
    };
    prototype.setValidationResultAttributes = function(vr, resultCode, refId, elementType, index) {
        vr.setCode(resultCode.toString());
        vr.setDesc(ValidationResultMappings.getValidationDescByCode(resultCode));
        vr.setSeverity(ValidationResultMappings.getValidationSeverityByCode(resultCode));
        if (refId != null) {
            vr.getRefIds().add(refId);
        }
        vr.setElementType(elementType);
        vr.setElementIndex(index);
    };
    prototype.validateBounds = function(instance, property, results) {
        var bound = null;
        var bit = property.getBounds().iterator();
         while (bit.hasNext()){
            bound = bit.next();
            if (Utils.isParsable(instance.getValue()) == true && ((bound.getMax() != null || bound.getMin() != null))) {
                if (bound.getType() == null || bound.getType() == DataType.NUMERIC) {
                    var propertyValue = Integer.parseInt(instance.getValue());
                    if (bound.getMax() != null && propertyValue > bound.getMax()) {
                        results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_MAX_EXCEEDED, property.getId(), ElementType.PRODUCT, 0));
                    } else if (bound.getMin() != null && propertyValue < bound.getMin()) {
                        results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_MIN_REQUIRED, property.getId(), ElementType.PRODUCT, 0));
                    }
                } else if (bound.getType() == DataType.DECIMAL) {
                    var propertyValue = Float.parseFloat(instance.getValue());
                    if (bound.getMax() != null && propertyValue > bound.getMax()) {
                        results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_MAX_EXCEEDED, property.getId(), ElementType.PRODUCT, 0));
                    } else if (bound.getMin() != null && propertyValue < bound.getMin()) {
                        results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_MIN_REQUIRED, property.getId(), ElementType.PRODUCT, 0));
                    }
                }
            }
            if (bound.getAllowedValues().size() > 0) {
                var allowedValue = false;
                var it = bound.getAllowedValues().iterator();
                 while (it.hasNext()){
                    var cgAllowedValues = it.next();
                    if (cgAllowedValues.getName() != null && cgAllowedValues.getName().equals(instance.getValue())) {
                        allowedValue = true;
                        break;
                    }
                }
                if (!allowedValue) {
                    results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_NOT_ALLOWED, property.getId(), ElementType.PRODUCT, 0));
                }
            }
            if (bound.getType() != null) {
                if (!Utils.validateFormat(bound.getType(), instance.getValue())) {
                    results.add(this.createValidationResult(ValidationResultCode.PROPERTY_VALUE_FORMAT_INVALID, property.getId(), ElementType.PRODUCT, 0));
                }
            }
        }
    };
    prototype.addRuleValidationResults = function(productRules, context, results) {
        if (context.getProduct() != null) {
            try {
                var rules = productRules.getRulesByRefIdAndType(context.getProduct().getId(), RuleType.VALIDATION);
                var result = null;
                var valRule = null;
                var it = rules.iterator();
                 while (it.hasNext()){
                    valRule = it.next().getDef();
                    result = valRule.evaluate(context);
                    if (result != null) 
                        results.add(result);
                }
            }catch (e) {
                 throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
            }
        }
    };
    prototype.addContentValidationResults = function(configuredProduct, productRules, context, results) {
        if (configuredProduct.getContentRequirements().isEmpty()) {
            return;
        }
        var crIdIt = null;
        var crGroupIt = null;
        var crMap = this.buildCrMap(configuredProduct.getContentRequirements());
        var caMap = this.buildCaMap(context.getProduct().getContentAssociations());
        crIdIt = caMap.keySet().iterator();
         while (crIdIt.hasNext()){
            var crId = crIdIt.next();
            if (crMap.get(crId) != null) {
                this.validateContent(results, crMap.get(crId), caMap.get(crId), ElementType.PRODUCT, 0);
            } else {
                results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_NOT_ALLOWED, ElementType.PRODUCT, 0, null, null, null, null));
                return;
            }
        }
        crIdIt = crMap.keySet().iterator();
        var caCrids = caMap.keySet();
         while (crIdIt.hasNext()){
            var crId = crIdIt.next();
            if (!caCrids.contains(crId)) {
                results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, ElementType.PRODUCT, 0, null, null, crMap.get(crId).getPurpose(), null));
            }
        }
        var caGroupMap = this.buildCaGroupMap(crMap, caMap);
        crGroupIt = caGroupMap.keySet().iterator();
        var caListIt = null;
        var pageGroupIt = null;
        var ca = null;
        var pg = null;
        var width = 0;
        var height = 0;
        var orientation = null;
         while (crGroupIt.hasNext()){
            width = 0;
            height = 0;
            orientation = null;
            caListIt = (caGroupMap.get(crGroupIt.next())).iterator();
             while (caListIt.hasNext()){
                ca = caListIt.next();
                pageGroupIt = ca.getPageGroups().iterator();
                 while (pageGroupIt.hasNext()){
                    pg = pageGroupIt.next();
                    if (width == 0) {
                        width = pg.getWidth();
                        height = pg.getHeight();
                        orientation = pg.getOrientation();
                    } else {
                        this.validateMixedSize(pg, width, height, orientation, ca, crMap.get(ca.getContentReqId()), results, ElementType.PRODUCT, 0);
                        this.validateMixedOrientation(pg, orientation, ca, crMap.get(ca.getContentReqId()), results, ElementType.PRODUCT, 0);
                    }
                }
            }
        }
    };
    prototype.validateContent = function(results, cr, cas, elementType, index) {
        var pageCount = 0;
        var it = cas.iterator();
        var pageIt = null;
        var ca = null;
        var pageGroup = null;
        var width = 0;
        var height = 0;
        var orientation = null;
         while (it.hasNext()){
            ca = it.next();
            if (!ca.getPageGroups().isEmpty()) {
                width = ca.getPageGroups().get(0).getWidth();
                height = ca.getPageGroups().get(0).getHeight();
                orientation = ca.getPageGroups().get(0).getOrientation();
                this.validatePrintReady(ca, cr, results, elementType, index);
            }
            this.validateContentReference(ca, cr, results, elementType, index);
            pageIt = ca.getPageGroups().iterator();
             while (pageIt.hasNext()){
                pageGroup = pageIt.next();
                pageCount = pageCount + (pageGroup.getEnd() - pageGroup.getStart()) + 1;
                this.validatePageGroup(pageGroup, ca, cr, results, elementType, index);
                this.validateAllowedSize(pageGroup, ca, cr, results, elementType, index);
                this.validateMixedSize(pageGroup, width, height, orientation, ca, cr, results, elementType, index);
                this.validateMixedOrientation(pageGroup, orientation, ca, cr, results, elementType, index);
            }
        }
        this.validatePageCount(pageCount, cr, results, elementType, index);
        this.validateFileCount(cas, cr, results, elementType, index);
        this.validateMoreFilesAllowed(pageCount, cas, cr, results, elementType, index);
    };
    prototype.validateContentReference = function(ca, cr, results, elementType, index) {
        if ((ca.getContentReference() == null || ca.getContentReference().length == 0) && (ca.getPageGroups() == null || ca.getPageGroups().isEmpty())) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_REFERENCE_OR_PAGE_GROUP_REQUIRED, elementType, index, null, null, cr.getPurpose(), null));
        }
    };
    prototype.validateFileCount = function(cas, cr, results, elementType, index) {
        if (cr.getMaxFiles() != -1 && cas.size() > cr.getMaxFiles()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_FILE_COUNT_MAX_EXCEEDED, elementType, index, null, null, cr.getPurpose(), null));
        }
    };
    prototype.validatePrintReady = function(ca, cr, results, elementType, index) {
        if (cr.isRequiresPrintReady() && !ca.isPrintReady() && !ca.isPhysicalContent() && ca.getAsyncFileSource() == null) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PRINT_READY_REQUIRED, elementType, index, ca.getContentReference(), ca.getParentContentReference(), cr.getPurpose(), null));
        }
    };
    prototype.validatePageCount = function(pageCount, cr, results, elementType, index) {
        if (pageCount < cr.getMinPages()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PAGE_COUNT_MIN_REQUIRED, elementType, index, null, null, cr.getPurpose(), null));
        }
        if (pageCount > cr.getMaxPages() && cr.getMaxPages() != -1) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PAGE_COUNT_MAX_EXCEEDED, elementType, index, null, null, cr.getPurpose(), null));
        }
    };
    prototype.validateMoreFilesAllowed = function(pageCount, cas, cr, results, elementType, index) {
        if (cr.getMaxPages() == -1 && cr.getMaxFiles() == -1) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, elementType, index, null, null, cr.getPurpose(), null));
        } else if (cr.getMaxPages() != -1 && cr.getMaxFiles() != -1 && pageCount < cr.getMaxPages() && cas.size() < cr.getMaxFiles()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, elementType, index, null, null, cr.getPurpose(), null));
        } else if (cr.getMaxPages() == -1 && cas.size() < cr.getMaxFiles()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, elementType, index, null, null, cr.getPurpose(), null));
        } else if (cr.getMaxFiles() == -1 && pageCount < cr.getMaxPages()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_MORE_FILES_ALLOWED, elementType, index, null, null, cr.getPurpose(), null));
        }
    };
    prototype.validateMixedSize = function(pageGroup, width, height, orientation, ca, cr, results, elementType, index) {
        if (!cr.isAllowMixedSize() && ((orientation == pageGroup.getOrientation() && (width != pageGroup.getWidth() || height != pageGroup.getHeight())) || (orientation != pageGroup.getOrientation() && (width != pageGroup.getHeight() || height != pageGroup.getWidth())))) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PAGE_SIZE_MIXED_NOT_ALLOWED, elementType, index, ca.getContentReference(), ca.getParentContentReference(), cr.getPurpose(), ca.getPageGroups()));
        }
    };
    prototype.validateMixedOrientation = function(pageGroup, orientation, ca, cr, results, elementType, index) {
        if (!cr.isAllowMixedOrientation() && (orientation != pageGroup.getOrientation())) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_ORIENTATION_MIXED_NOT_ALLOWED, elementType, index, ca.getContentReference(), ca.getParentContentReference(), cr.getPurpose(), ca.getPageGroups()));
        }
    };
    prototype.validatePageGroup = function(pageGroup, ca, cr, results, elementType, index) {
        if (pageGroup.getStart() > pageGroup.getEnd()) {
            results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PAGE_GROUP_INVALID, elementType, index, ca.getContentReference(), ca.getParentContentReference(), cr.getPurpose(), ca.getPageGroups()));
        }
    };
    prototype.validateAllowedSize = function(pageGroup, ca, cr, results, elementType, index) {
        if (!cr.getAllowedSizes().isEmpty()) {
            if (!this.isAllowedSize(pageGroup.getWidth(), pageGroup.getHeight(), cr.getAllowedSizes())) {
                results.add(this.createContentValidationResult(ValidationResultCode.CONTENT_PAGE_SIZE_INVALID, elementType, index, ca.getContentReference(), ca.getParentContentReference(), cr.getPurpose(), ca.getPageGroups()));
            }
        }
    };
    prototype.isAllowedSize = function(width, height, dimensions) {
        var it = dimensions.iterator();
        var dimension = null;
         while (it.hasNext()){
            dimension = it.next();
            if ((width == dimension.getWidth() && height == dimension.getHeight()) || (height == dimension.getWidth() && width == dimension.getHeight())) {
                return true;
            }
        }
        return false;
    };
    prototype.buildCaGroupMap = function(crMap, caMap) {
        var caGroupMap = new ArrayMap();
        var caListIt = caMap.values().iterator();
        var caIt = null;
        var ca = null;
        var caGroupList = null;
        var contentGroup = null;
         while (caListIt.hasNext()){
            caIt = caListIt.next().iterator();
             while (caIt.hasNext()){
                ca = caIt.next();
                contentGroup = crMap.get(ca.getContentReqId()).getContentGroup();
                if (caGroupMap.get(contentGroup) != null) {
                    caGroupMap.get(contentGroup).add(ca);
                } else {
                    caGroupList = new ArrayList();
                    caGroupList.add(ca);
                    caGroupMap.put(contentGroup, caGroupList);
                }
            }
        }
        return caGroupMap;
    };
    prototype.buildCaMap = function(cas) {
        var caMap = new ArrayMap();
        var contentIt = cas.iterator();
        var ca = null;
        var caList = null;
         while (contentIt.hasNext()){
            ca = contentIt.next();
            if (caMap.get(ca.getContentReqId()) != null) {
                caMap.get(ca.getContentReqId()).add(ca);
            } else {
                caList = new ArrayList();
                caList.add(ca);
                caMap.put(ca.getContentReqId(), caList);
            }
        }
        return caMap;
    };
    prototype.buildCrMap = function(crs) {
        var crMap = new ArrayMap();
        var cr = null;
        var crit = crs.iterator();
         while (crit.hasNext()){
            cr = crit.next();
            crMap.put(cr.getId(), cr);
        }
        return crMap;
    };
    prototype.calculatePageCount = function(pi) {
        var pageCount = 0;
        var cas = pi.getContentAssociations();
        var it = cas.iterator();
        var pageGroupIt = null;
        var pageGroup = null;
         while (it.hasNext()){
            pageGroupIt = it.next().getPageGroups().iterator();
             while (pageGroupIt.hasNext()){
                pageGroup = pageGroupIt.next();
                pageCount = pageGroup.getEnd() - pageGroup.getStart() + 1;
            }
        }
        return pageCount;
    };
    /**
     *  Build a ProductInstance from the specified product with all of the defaults either in the base product or override rules
     *  @param product to create the instance from
     *  @param preset 
     *  @param ph 
     *  @return a new ProductInstance containing all of the default for the specified product
     */
    prototype.buildProductInstance = function(product) {
        var instance = new ProductInstance();
        var propertyInstance = null;
        instance.setId(product.getId());
        instance.setVersion(product.getVersion());
        instance.setName(product.getName());
        instance.setQty(product.getQty());
        instance.setPriceable(product.getPriceable());
        instance.setInstanceId((new Date()).getTime());
        instance.setProofRequired(product.isProofRequired());
        var itProperty = product.getProperties().iterator();
        var property = null;
         while (itProperty.hasNext()){
            property = itProperty.next();
            propertyInstance = new PropertyInstance();
            propertyInstance.setId(property.getId());
            propertyInstance.setName(property.getName());
            propertyInstance.setValue(property.getValue());
            instance.getProperties().add(propertyInstance);
        }
        var itFeature = product.getFeatures().iterator();
        var fInstance = null;
        var feature = null;
         while (itFeature.hasNext()){
            feature = itFeature.next();
            if (feature.getDefaultChoiceId() != null) {
                fInstance = this.buildFeatureInstance(feature);
                instance.addFeature(fInstance);
            }
        }
        return instance;
    };
    /**
     *  Build a FeatureInstance from the specified feature with all of the defaults either in the base feature or override rules
     *  @param feature to create the instance from
     *  @return a new FeatureInstance containing all of the default for the specified feature
     */
    prototype.buildFeatureInstance = function(feature) {
        var instance = new FeatureInstance();
        instance.setId(feature.getId());
        instance.setName(feature.getName());
        var defaultChoiceId = feature.getDefaultChoiceId();
        if (defaultChoiceId != null) {
            var itChoice = feature.getChoices().iterator();
            var choice = null;
             while (itChoice.hasNext()){
                choice = itChoice.next();
                if (choice.getId().equals(defaultChoiceId)) {
                    instance.setChoice(this.buildChoiceInstance(choice));
                    break;
                }
            }
        }
        return instance;
    };
    /**
     *  Build a FeatureInstance from the specified feature with all of the defaults either in the base feature or override rules
     *  @param feature to create the instance from
     *  @return a new FeatureInstance containing all of the default for the specified feature
     */
    prototype.buildFeatureInstanceFromConfigured = function(feature) {
        var instance = new FeatureInstance();
        instance.setId(feature.getId());
        instance.setName(feature.getName());
        var defaultChoiceId = feature.getDefaultChoiceId();
        if (defaultChoiceId != null) {
            var itChoice = feature.getChoices().iterator();
            var choice = null;
             while (itChoice.hasNext()){
                choice = itChoice.next();
                if (choice.getId() == defaultChoiceId) {
                    instance.setChoice(this.buildChoiceInstanceFromConfigured(choice));
                    break;
                }
            }
        }
        return instance;
    };
    /**
     *  Build a ChoiceInstance from the specified choice
     *  @param choice to create the instance from
     *  @return a new ChoiceInstance
     */
    prototype.buildChoiceInstance = function(choice) {
        var ci = new ChoiceInstance();
        ci.setId(choice.getId());
        ci.setName(choice.getName());
        var pit = choice.getProperties().iterator();
        var prop = null;
        var pis = new ArraySet();
         while (pit.hasNext()){
            prop = pit.next();
            pis.add(this.buildPropertyInstance(prop));
        }
        ci.setProperties(pis);
        return ci;
    };
    /**
     *  Build a ChoiceInstance from the specified choice
     *  @param choice to create the instance from
     *  @return a new ChoiceInstance
     */
    prototype.buildChoiceInstanceFromConfigured = function(choice) {
        var ci = new ChoiceInstance();
        ci.setId(choice.getId());
        ci.setName(choice.getName());
        var pit = choice.getProperties().iterator();
        var prop = null;
        var pis = new ArraySet();
         while (pit.hasNext()){
            prop = pit.next();
            pis.add(this.buildPropertyInstance(prop));
        }
        ci.setProperties(pis);
        return ci;
    };
    prototype.buildPropertyInstance = function(property) {
        var pi = new PropertyInstance();
        pi.setId(property.getId());
        pi.setName(property.getName());
        pi.setValue(property.getValue());
        return pi;
    };
    prototype.buildPageExceptionInstance = function(afe) {
        var fe = new PageExceptionInstance();
        fe.setId(afe.getId());
        fe.setName(afe.getName());
        var pit = afe.getProperties().iterator();
        var prop = null;
        var pis = new ArraySet();
         while (pit.hasNext()){
            prop = pit.next();
            pis.add(this.buildPropertyInstance(prop));
        }
        fe.setProperties(pis);
        return fe;
    };
    prototype.addChoiceToInstance = function(choice, feature, featureContainer) {
        var featureInstance = featureContainer.getFeatureById(feature.getId());
        if (featureInstance == null) {
            featureInstance = this.buildFeatureInstanceFromConfigured(feature);
            featureContainer.addFeature(featureInstance);
        }
        featureInstance.setChoice(this.buildChoiceInstanceFromConfigured(choice));
    };
    prototype.getSelectedChoiceForFeatureId = function(featureId, featureContainer) {
        var featureInstance = featureContainer.getFeatureById(featureId);
        if (featureInstance != null && featureInstance.getChoice() != null) {
            return featureInstance.getChoice();
        }
        return null;
    };
    prototype.applyConfiguredToProperties = function(cfgProperties, instanceProperties) {
        var cfgProperty = null;
        var propertyInstance = null;
        var cfgPit = cfgProperties.iterator();
         while (cfgPit.hasNext()){
            cfgProperty = cfgPit.next();
            propertyInstance = Utils.getElementById(cfgProperty.getId(), instanceProperties);
            if (propertyInstance == null) {
                propertyInstance = new PropertyInstance();
                propertyInstance.setId(cfgProperty.getId());
                propertyInstance.setName(cfgProperty.getName());
                propertyInstance.setValue(cfgProperty.getValue());
                instanceProperties.add(propertyInstance);
            } else if (cfgProperty.isInputAllowed()) {
                if (!cfgProperty.getBounds().isEmpty()) {
                    var boundIt = cfgProperty.getBounds().iterator();
                     while (boundIt.hasNext()){
                        var bound = boundIt.next();
                        if (bound.getAllowedValues().size() > 0) {
                            var allowedValuesIt = bound.getAllowedValues().iterator();
                            var firstPropValue = true;
                            var cfgPropertyValue = null;
                            var validPropValue = false;
                             while (allowedValuesIt.hasNext()){
                                var propAllowedValue = allowedValuesIt.next();
                                if (firstPropValue) {
                                    cfgPropertyValue = propAllowedValue.getName();
                                    firstPropValue = false;
                                }
                                if (propAllowedValue.getName().equals(propertyInstance.getValue())) {
                                    validPropValue = true;
                                    break;
                                }
                            }
                            if (!validPropValue) {
                                propertyInstance.setValue(cfgPropertyValue);
                            }
                        }
                    }
                } else if (cfgProperty.isOverrideWithConfiguredValue()) {
                    propertyInstance.setValue(cfgProperty.getValue());
                }
            } else {
                if (!cfgProperty.isInputAllowed()) {
                    propertyInstance.setValue(cfgProperty.getValue());
                }
            }
        }
    };
    prototype.getSelectableDefaultChoice = function(feature) {
        var defaultChoice = feature.getChoiceById(feature.getDefaultChoiceId());
        if (defaultChoice != null && defaultChoice.isSelectable()) {
            return defaultChoice;
        }
        var it = feature.getChoices().iterator();
         while (it.hasNext()){
            defaultChoice = it.next();
            if (defaultChoice.isSelectable()) {
                return defaultChoice;
            }
        }
        return null;
    };
    prototype.applyConfiguredProductToInstance = function(product, instance, applyDefaults) {
        this.applyConfiguredToProperties(product.getProperties(), instance.getProperties());
        this.applyConfiguredToFeatures(product.getFeatures(), instance, applyDefaults, true);
        this.applyConfiguredPageExceptionToInstance(product, instance, applyDefaults);
    };
    prototype.applyConfiguredToFeatures = function(cfgFeatures, container, applyDefaults, addDefaults) {
        var fi = null;
        var ci = null;
        var cfgFeature = null;
        var cfgChoice = null;
        var cfgFit = cfgFeatures.iterator();
         while (cfgFit.hasNext()){
            cfgFeature = cfgFit.next();
            if (cfgFeature.isSelectable()) {
                if (cfgFeature.getDefaultChoiceId() != null && !container.containsFeature(cfgFeature.getId()) && addDefaults) {
                    fi = this.buildFeatureInstanceFromConfigured(cfgFeature);
                    container.addFeature(fi);
                }
            } else {
                if (container.containsFeature(cfgFeature.getId())) {
                    fi = container.getFeatureById(cfgFeature.getId());
                    container.removeFeature(fi);
                }
            }
            if (container.containsFeature(cfgFeature.getId())) {
                if (cfgFeature.isOverrideWithDefault() && applyDefaults) {
                    ci = this.buildChoiceInstanceFromConfigured(cfgFeature.getChoiceById(cfgFeature.getDefaultChoiceId()));
                    fi = container.getFeatureById(cfgFeature.getId());
                    fi.setChoice(ci);
                    cfgFeature.setOverrideWithDefault(false);
                } else {
                    ci = container.getFeatureById(cfgFeature.getId()).getChoice();
                    cfgChoice = cfgFeature.getChoiceById(ci.getId());
                    if (!cfgChoice.isSelectable()) {
                        cfgChoice = this.getSelectableDefaultChoice(cfgFeature);
                        if (cfgChoice != null) {
                            ci = this.buildChoiceInstanceFromConfigured(cfgChoice);
                            fi = container.getFeatureById(cfgFeature.getId());
                            fi.setChoice(ci);
                        }
                    } else {
                        this.applyConfiguredToProperties(cfgChoice.getProperties(), ci.getProperties());
                    }
                }
            }
        }
    };
    prototype.applyConfiguredPageExceptionToInstance = function(product, instance, applyDefaults) {
        var cfgPeIt = product.getPageExceptions().iterator();
        var peIt = null;
        var cfgPe = null;
        var pe = null;
        var indexes = new ArraySet();
        var index = 0;
         while (cfgPeIt.hasNext()){
            cfgPe = cfgPeIt.next();
            index = 0;
            peIt = instance.getPageExceptions().iterator();
             while (peIt.hasNext()){
                pe = peIt.next();
                if (Utils.convertToLongvalue(pe.getId()) == Utils.convertToLongvalue(cfgPe.getId())) {
                    if (cfgPe.isSelectable()) {
                        this.applyConfiguredToFeatures(cfgPe.getFeatures(), pe, applyDefaults, false);
                    } else {
                        indexes.add(index);
                    }
                }
                index++;
            }
        }
        var indexIt = indexes.iterator();
         while (indexIt.hasNext()){
            instance.getPageExceptions().remove(indexIt.next());
        }
    };
    prototype.applyPresetToProductInstance = function(preset, product, context) {
        this.applyPresetForNoOverlay(preset, product, context);
    };
    prototype.applyPresetForNoOverlay = function(preset, product, context) {
        var productInstance = context.getProduct();
        var cfIdMap = this.getChoiceFeatureMap(product);
        var featureId = null;
        var presetChoice = null;
        var feature = null;
        if (preset.getQty() > 0) {
            productInstance.setQty(preset.getQty());
        }
        productInstance.removeAllFeatures();
        var pcit = preset.getChoices().iterator();
         while (pcit.hasNext()){
            presetChoice = pcit.next();
            if (presetChoice.isSelect()) {
                featureId = cfIdMap.get(presetChoice.getId());
                feature = Utils.getElementById(featureId, product.getFeatures());
                var fi = new FeatureInstance();
                fi.setId(feature.getId());
                fi.setName(feature.getName());
                fi.setChoice(this.buildChoiceInstance(Utils.getElementById(presetChoice.getId(), feature.getChoices())));
                productInstance.addFeature(fi);
            }
        }
    };
    prototype.applyChoiceIds = function(choiceIds, product, productInstance) {
        if (!choiceIds.isEmpty()) {
            var it = choiceIds.iterator();
            var cfIdMap = this.getChoiceFeatureMap(product);
            var featureId = null;
            var feature = null;
             while (it.hasNext()){
                var choiceId = it.next();
                featureId = cfIdMap.get(choiceId);
                if (featureId != null) {
                    productInstance.removeFeaturebyId(featureId);
                    feature = Utils.getElementById(featureId, product.getFeatures());
                    var fi = new FeatureInstance();
                    fi.setId(feature.getId());
                    fi.setName(feature.getName());
                    fi.setChoice(this.buildChoiceInstance(Utils.getElementById(choiceId, feature.getChoices())));
                    productInstance.addFeature(fi);
                }
            }
        }
    };
    prototype.buildContentAssociation = function(product, productInstance, contentHints) {
        var purposeCrMap = this.buildPurposeCrMap(product.getContentRequirements());
        var caList = new ArrayList();
        if (contentHints != null && contentHints.size() > 0) {
            var chIt = contentHints.iterator();
             while (chIt.hasNext()){
                var contentHint = chIt.next();
                if (contentHint.getContentAssociation() != null) {
                    this.copyContentForCr(contentHint.getContentAssociation(), purposeCrMap, productInstance, caList);
                } else {
                    this.buildContentAssociationForCr(contentHint, purposeCrMap, productInstance, caList);
                }
            }
        } else {
            this.buildContentAssociationForCr(null, purposeCrMap, productInstance, caList);
        }
        return caList;
    };
    prototype.copyContentForCr = function(contentHintCa, purposeCrMap, pi, caList) {
        var cr = purposeCrMap.get(contentHintCa.getPurpose());
        if (cr != null) {
            var ca = new ContentAssociation();
            ca.setContentReference(contentHintCa.getContentReference());
            ca.setContentReplacementUrl(contentHintCa.getContentReplacementUrl());
            ca.setContentReqId(cr.getId());
            ca.setContentType(contentHintCa.getContentType());
            ca.setDesc(cr.getDesc());
            ca.setFileName(contentHintCa.getFileName());
            ca.setName(cr.getName());
            ca.setPageGroups(contentHintCa.getPageGroups());
            ca.setParentContentReference(contentHintCa.getParentContentReference());
            ca.setPhysicalContent(contentHintCa.isPhysicalContent());
            ca.setPrintReady(contentHintCa.isPrintReady());
            ca.setPurpose(contentHintCa.getPurpose());
            ca.setSpecialInstructions(contentHintCa.getSpecialInstructions());
            ca.setFileSource(contentHintCa.getFileSource());
            caList.add(ca);
        } else {
            cr = purposeCrMap.get("MAIN_CONTENT");
            if (cr == null) {
                cr = purposeCrMap.get("SINGLE_SHEET_FRONT");
                purposeCrMap.remove("SINGLE_SHEET_FRONT");
            }
            if (cr == null) {
                cr = purposeCrMap.get("SINGLE_SHEET_BACK");
            }
            var mergedCa = false;
            if (Utils.isStringsEqual("MAIN_CONTENT", cr.getPurpose())) {
                var ca = this.getMatchingCa(contentHintCa, caList);
                if (ca != null) {
                    this.mergePageGroups(contentHintCa, ca);
                    mergedCa = true;
                }
            }
            if (!mergedCa) {
                var availablePageCount = this.getTotalPageGroupPageCount(contentHintCa.getPageGroups());
                var ca = new ContentAssociation();
                ca.setContentReference(contentHintCa.getContentReference());
                ca.setContentReplacementUrl(contentHintCa.getContentReplacementUrl());
                ca.setParentContentReference(contentHintCa.getParentContentReference());
                ca.setContentType(contentHintCa.getContentType());
                ca.setPhysicalContent(contentHintCa.isPhysicalContent());
                ca.setPrintReady(contentHintCa.isPrintReady());
                ca.setSpecialInstructions(contentHintCa.getSpecialInstructions());
                ca.setFileSource(contentHintCa.getFileSource());
                ca.setPurpose(cr.getPurpose());
                ca.setContentReqId(cr.getId());
                ca.setDesc(cr.getDesc());
                ca.setName(cr.getName());
                ca.setFileName(contentHintCa.getFileName());
                caList.add(ca);
                var pendingPageGroups = new ArrayList();
                var clearedPageGroups = new ArrayList();
                if (availablePageCount > 0) {
                    if (availablePageCount <= cr.getMaxPages() || cr.getMaxPages() == -1) {
                        ca.setPageGroups(contentHintCa.getPageGroups());
                    } else {
                        var pgIt = contentHintCa.getPageGroups().iterator();
                         while (pgIt.hasNext()){
                            var pg = pgIt.next();
                            if (pendingPageGroups.isEmpty()) {
                                if (pg.getEnd() <= cr.getMaxPages() || "MAIN_CONTENT".equals(cr.getPurpose()) || "SINGLE_SHEET_BACK".equals(cr.getPurpose())) {
                                    clearedPageGroups.add(pg);
                                } else {
                                    var clearedPg = new PageGroup();
                                    clearedPg.setStart(pg.getStart());
                                    clearedPg.setEnd(cr.getMaxPages());
                                    clearedPg.setHeight(pg.getHeight());
                                    clearedPg.setWidth(pg.getWidth());
                                    clearedPg.setOrientation(pg.getOrientation());
                                    clearedPageGroups.add(clearedPg);
                                    var pendingPg = new PageGroup();
                                    pendingPg.setStart(Utils.convertToInteger(clearedPg.getStart()) + Utils.convertToInteger(clearedPg.getEnd()));
                                    pendingPg.setEnd(pg.getEnd());
                                    pendingPg.setHeight(pg.getHeight());
                                    pendingPg.setWidth(pg.getWidth());
                                    pendingPg.setOrientation(pg.getOrientation());
                                    pendingPageGroups.add(pendingPg);
                                }
                            } else {
                                pendingPageGroups.add(pg);
                            }
                        }
                        ca.setPageGroups(clearedPageGroups);
                        cr = purposeCrMap.get("SINGLE_SHEET_BACK");
                        if (cr != null) {
                            var backCa = new ContentAssociation();
                            backCa.setContentReference(contentHintCa.getContentReference());
                            backCa.setContentReplacementUrl(contentHintCa.getContentReplacementUrl());
                            backCa.setParentContentReference(contentHintCa.getParentContentReference());
                            backCa.setContentType(contentHintCa.getContentType());
                            backCa.setPhysicalContent(contentHintCa.isPhysicalContent());
                            backCa.setPrintReady(contentHintCa.isPrintReady());
                            backCa.setSpecialInstructions(contentHintCa.getSpecialInstructions());
                            backCa.setFileSource(contentHintCa.getFileSource());
                            backCa.setPurpose(cr.getPurpose());
                            backCa.setContentReqId(cr.getId());
                            backCa.setDesc(cr.getDesc());
                            backCa.setName(cr.getName());
                            backCa.setPageGroups(pendingPageGroups);
                            backCa.setFileName(contentHintCa.getFileName());
                            caList.add(backCa);
                        } else {
                            ca.setPageGroups(contentHintCa.getPageGroups());
                        }
                    }
                }
            }
        }
    };
    prototype.getTotalPageGroupPageCount = function(pageGroups) {
        var pageCount = 0;
        var pgIt = pageGroups.iterator();
         while (pgIt.hasNext()){
            var pg = pgIt.next();
            pageCount = pageCount + (pg.getEnd() - pg.getStart()) + 1;
        }
        return pageCount;
    };
    prototype.buildContentAssociationForCr = function(contentHint, purposeCrMap, pi, caList) {
        var cr = purposeCrMap.get("MAIN_CONTENT");
        if (cr == null) {
            cr = purposeCrMap.get("SINGLE_SHEET_FRONT");
            purposeCrMap.remove("SINGLE_SHEET_FRONT");
        }
        if (cr == null) {
            cr = purposeCrMap.get("SINGLE_SHEET_BACK");
        }
        if (cr != null) {
            var availablePageCount = 0;
            var ca = new ContentAssociation();
            var pgList = new ArrayList();
            ca.setPurpose(cr.getPurpose());
            ca.setContentReqId(cr.getId());
            ca.setPrintReady(true);
            var pg = this.buildBasePageGroup(cr.getMinPages(), pi);
            if (contentHint != null && contentHint.getHeight() != null && contentHint.getWidth() != null) {
                pg.setHeight(Float.parseFloat(contentHint.getHeight()));
                pg.setWidth(Float.parseFloat(contentHint.getWidth()));
            }
            availablePageCount = this.getTotalPageCount(contentHint, pi, cr.getMinPages());
            if (availablePageCount > 0 && (availablePageCount <= cr.getMaxPages() || cr.getMaxPages() == -1)) {
                pg.setEnd(availablePageCount);
                availablePageCount = 0;
            } else {
                availablePageCount = availablePageCount - cr.getMinPages();
            }
            pgList.add(pg);
            ca.setPageGroups(pgList);
            caList.add(ca);
            if (availablePageCount > 0) {
                cr = purposeCrMap.get("SINGLE_SHEET_BACK");
                if (cr != null && !"SINGLE_SHEET_BACK".equals(ca.getPurpose())) {
                    ca = new ContentAssociation();
                    ca.setPurpose(cr.getPurpose());
                    ca.setContentReqId(cr.getId());
                    ca.setPrintReady(true);
                    pg = this.buildBasePageGroup(cr.getMinPages(), pi);
                    if (contentHint != null && contentHint.getHeight() != null && contentHint.getWidth() != null) {
                        pg.setHeight(Float.parseFloat(contentHint.getHeight()));
                        pg.setWidth(Float.parseFloat(contentHint.getWidth()));
                    }
                    pg.setEnd(availablePageCount);
                    pgList = new ArrayList();
                    pgList.add(pg);
                    ca.setPageGroups(pgList);
                    caList.add(ca);
                } else {
                    ca = caList.get(caList.size() - 1);
                    var pageGroup = ca.getPageGroups().get(0);
                    pageGroup.setEnd(pageGroup.getEnd() + availablePageCount);
                }
            }
        }
    };
    prototype.buildBasePageGroup = function(minPages, productInstance) {
        var pg = new PageGroup();
        pg.setStart(1);
        pg.setEnd(minPages);
        var fit = productInstance.getFeatures().iterator();
         while (fit.hasNext()){
            var featureInstance = fit.next();
            var pIt = featureInstance.getChoice().getProperties().iterator();
             while (pIt.hasNext()){
                var pi = pIt.next();
                if (pi.getName().equals("MEDIA_HEIGHT")) {
                    pg.setHeight(Float.parseFloat(pi.getValue()));
                } else if (pi.getName().equals("MEDIA_WIDTH")) {
                    pg.setWidth(Float.parseFloat(pi.getValue()));
                } else if (pi.getName().equals("PAGE_ORIENTATION")) {
                    pg.setOrientation(ContentOrientation.valueOf(pi.getValue()));
                }
            }
        }
        return pg;
    };
    prototype.getTotalPageCount = function(contentHint, productInstance, minPages) {
        var totalPageCount = 0;
        if (contentHint != null && contentHint.getPageCount() > 0) {
            totalPageCount = totalPageCount + contentHint.getPageCount();
        } else {
            var fit = productInstance.getFeatures().iterator();
             while (fit.hasNext()){
                var featureInstance = fit.next();
                var pIt = featureInstance.getChoice().getProperties().iterator();
                 while (pIt.hasNext()){
                    var pi = pIt.next();
                    if (pi.getName().equals("SIDE")) {
                        if (pi.getValue() != null && pi.getValue().equals("DOUBLE")) {
                            totalPageCount = 2;
                        } else {
                            totalPageCount = 1;
                        }
                        totalPageCount = totalPageCount < minPages ? minPages : totalPageCount;
                        break;
                    }
                }
            }
        }
        return totalPageCount;
    };
    prototype.mergePageGroups = function(contentHintCa, ca) {
        var sourcePgIt = contentHintCa.getPageGroups().iterator();
         while (sourcePgIt.hasNext()){
            var sourcePg = sourcePgIt.next();
            var destPgIt = ca.getPageGroups().iterator();
            var matchFound = false;
             while (destPgIt.hasNext()){
                var destPg = destPgIt.next();
                if (destPg.getHeight() == sourcePg.getHeight() && destPg.getWidth() == sourcePg.getWidth()) {
                    if (destPg.getEnd() < sourcePg.getStart()) {
                        destPg.setEnd(sourcePg.getEnd());
                    } else {
                        destPg.setStart(sourcePg.getStart());
                    }
                    matchFound = true;
                }
            }
            if (!matchFound) {
                ca.getPageGroups().add(sourcePg);
            }
        }
    };
    prototype.getMatchingCa = function(contentHintCa, caList) {
        var caIt = caList.iterator();
         while (caIt.hasNext()){
            var ca = caIt.next();
            if (ca.getContentReference() != null && contentHintCa.getContentReference() != null && Utils.isStringsEqual(ca.getContentReference(), contentHintCa.getContentReference())) {
                return ca;
            }
        }
        return null;
    };
    prototype.buildPurposeCrMap = function(contentRequirements) {
        var purposeCrMap = new ArrayMap();
        var crIt = contentRequirements.iterator();
        var cr = null;
         while (crIt.hasNext()){
            cr = crIt.next();
            if (("MAIN_CONTENT").equals(cr.getPurpose())) {
                purposeCrMap.put("MAIN_CONTENT", cr);
            } else if (("SINGLE_SHEET_FRONT").equals(cr.getPurpose())) {
                purposeCrMap.put("SINGLE_SHEET_FRONT", cr);
            } else if (("SINGLE_SHEET_BACK").equals(cr.getPurpose())) {
                purposeCrMap.put("SINGLE_SHEET_BACK", cr);
            }
        }
        return purposeCrMap;
    };
    prototype.getChoiceFeatureMap = function(product) {
        var map = new ArrayMap();
        var choice = null;
        var feature = null;
        var fit = product.getFeatures().iterator();
        var cit = null;
         while (fit.hasNext()){
            feature = fit.next();
            cit = feature.getChoices().iterator();
             while (cit.hasNext()){
                choice = cit.next();
                map.put(choice.getId(), feature.getId());
            }
        }
        return map;
    };
    /**
     *  This flag overrides the priceable flag.
     *  @param entityId
     *  @param productRules
     *  @param context
     *  @return
     *  @throws RuleConfigurationException
     *  @throws ProductConfigProcessorException
     */
    prototype.isProductPriceable = function(product, productRules, context) {
        var isPriceable = product.getPriceable();
        var rule = productRules.getSingleRuleDef(product.getId(), RuleType.PRICEABLE);
        if (rule != null) {
            isPriceable = rule.evaluate(context);
        }
        return isPriceable;
    };
    /**
     *  This flag overrides the Quantity.
     *  @param productRules
     *  @param context
     *  @return
     *  @throws RuleConfigurationException
     *  @throws ProductConfigProcessorException
     *  Added by Radha
     */
    prototype.applyOverrideProductQty = function(product, productRules, context) {
        var productQuantity = product.getQty();
        var rule = productRules.getSingleRuleDef(product.getId(), RuleType.PRODUCT_QTY);
        if (rule != null) {
            productQuantity = rule.evaluateType(context, ValueType.INTEGER);
        }
        return productQuantity;
    };
    prototype.isProofRequired = function(product, productRules, context) {
        var isProofRequired = product.isProofRequired();
        var rule = productRules.getSingleRuleDef(product.getId(), RuleType.PROOF_FLAG);
        if (rule != null) {
            isProofRequired = rule.evaluate(context);
        }
        return isProofRequired;
    };
    prototype.getIsOutSourced = function(product, productRules, context) {
        var outSourced = context.getProduct().getIsOutSourced();
        var rule = productRules.getSingleRuleDef(product.getId(), RuleType.OUTSOURCE);
        if (rule != null) {
            outSourced = rule.evaluate(context);
        }
        return outSourced;
    };
    prototype.buildCompatabilityGroups = function(context, choiceMap) {
        var cgMap = new ArrayMap();
        var choice = null;
        var cg = null;
        var cIdIt = null;
        var ci = null;
        var cgIt = null;
        var fit = context.getProduct().getFeatures().iterator();
        var selectedChoiceCgNames = new ArraySet();
        cIdIt = context.getSelectedChoiceIds().iterator();
         while (cIdIt.hasNext()){
            choice = choiceMap.get(cIdIt.next());
            if (choice != null) {
                cgIt = choice.getCompatibilityGroups().iterator();
                 while (cgIt.hasNext()){
                    cg = cgIt.next();
                    selectedChoiceCgNames.add(cg.getName());
                    this.buildCompatabilityMap(cg, cgMap);
                }
            }
        }
         while (fit.hasNext()){
            ci = fit.next().getChoice();
            if (ci != null) {
                choice = choiceMap.get(ci.getId());
                if (choice != null && !context.getSelectedChoiceIds().contains(choice.getId())) {
                    cgIt = choice.getCompatibilityGroups().iterator();
                     while (cgIt.hasNext()){
                        cg = cgIt.next();
                        if (selectedChoiceCgNames.contains(cg.getName())) {
                            var csgNames = new ArraySet();
                            var csgIt = cg.getCompatibilitySubGroups().iterator();
                             while (csgIt.hasNext()){
                                var csgName = csgIt.next().getName();
                                if (csgName == "ALL") {
                                    if (cgMap.get(cg.getName()) == null) {
                                        csgNames.add(csgName);
                                        cgMap.put(cg.getName(), csgNames);
                                    } else {
                                        cgMap.get(cg.getName()).add(csgName);
                                    }
                                    break;
                                }
                            }
                        } else {
                            this.buildCompatabilityMap(cg, cgMap);
                        }
                    }
                }
            }
        }
        return cgMap;
    };
    prototype.buildCompatabilityMap = function(cg, cgMap) {
        var csgNames = new ArraySet();
        var csgIt = cg.getCompatibilitySubGroups().iterator();
         while (csgIt.hasNext()){
            csgNames.add(csgIt.next().getName());
        }
        if (cgMap.get(cg.getName()) == null) {
            cgMap.put(cg.getName(), csgNames);
        } else {
            cgMap.get(cg.getName()).addAll(csgNames);
        }
    };
    prototype.isChoiceCompatible = function(cgBaseSet, cgInstanceMap) {
        var isSelectable = false;
        var cgBase = null;
        var csgInstanceNames = null;
        var csgBase = null;
        var cgBaseIt = cgBaseSet.iterator();
        var csgBaseIt = null;
         while (cgBaseIt.hasNext()){
            cgBase = cgBaseIt.next();
            if (cgInstanceMap.get(cgBase.getName()) != null) {
                csgBaseIt = cgBase.getCompatibilitySubGroups().iterator();
                csgInstanceNames = cgInstanceMap.get(cgBase.getName());
                isSelectable = false;
                if (cgBase.getCompatibilitySubGroups().isEmpty() && csgInstanceNames.isEmpty()) {
                    isSelectable = true;
                } else {
                     while (csgBaseIt.hasNext()){
                        csgBase = csgBaseIt.next();
                        if (csgInstanceNames.contains(csgBase.getName())) {
                            isSelectable = true;
                            break;
                        }
                    }
                }
                if (!isSelectable) {
                    return false;
                }
            }
        }
        return true;
    };
    /**
     *  Generate Product Summary from Product Instance.
     */
    prototype.generateProductSummary = function(instance) {
        var piSummary = new ProductInstanceSummary();
        var fcMap = null;
        var prdInstDt = new ProductInstanceDetails();
        var detailsList = new ArrayList();
        var parentContentReference = null;
        piSummary.setProductName(instance.getName());
        piSummary.setUserProductName(instance.getUserProductName());
        var pgGrpEnd = 0;
        var conAssociations = instance.getContentAssociations().iterator();
         while (conAssociations.hasNext()){
            var eachConAssociation = conAssociations.next();
            if (eachConAssociation != null) {
                var pgGrps = eachConAssociation.getPageGroups().iterator();
                 while (pgGrps.hasNext()){
                    var pgGrp = pgGrps.next();
                    pgGrpEnd = pgGrpEnd + pgGrp.getEnd();
                    if (parentContentReference == null || (!parentContentReference.equals(eachConAssociation.getParentContentReference()))) {
                        prdInstDt = new ProductInstanceDetails();
                        prdInstDt.setFeatureChoiceMap(this.buildFeatureChoiceMap(instance.getFeatures()));
                        prdInstDt.setFileName(eachConAssociation.getFileName());
                        prdInstDt.setType(ElementType.PRODUCT);
                        detailsList.add(prdInstDt);
                    }
                    parentContentReference = eachConAssociation.getParentContentReference();
                }
            }
        }
        piSummary.setDetails(detailsList);
        return piSummary;
    };
    /**
     *  Method to generate summary of Feature/Choice in Product Instance
     *  @param fileName
     *  @param featureSet
     *  @param type
     *  @return
     */
    prototype.buildFeatureChoiceMap = function(featureSet) {
        var fcOptions = new ArrayMap();
        var fit = featureSet.iterator();
         while (fit.hasNext()){
            var feature = fit.next();
            if (feature.getName() != null) {
                var ci = feature.getChoice();
                if (ci != null) {
                    fcOptions.put(feature.getName(), ci.getName());
                }
            }
        }
        return fcOptions;
    };
    prototype.getConfiguredAllowedValues = function(propertyAllowedValue, productRules, context) {
        try {
            if (this.isValueSelectable(propertyAllowedValue.getId(), productRules, context)) {
                var configAllowedValues = new PropertyAllowedValue();
                configAllowedValues.setId(propertyAllowedValue.getId());
                configAllowedValues.setName(propertyAllowedValue.getName());
                return configAllowedValues;
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
        return null;
    };
    prototype.isValueSelectable = function(entityId, productRules, context) {
        var rule = productRules.getSingleRuleDef(entityId, RuleType.AVAILABLE);
        return (rule == null || rule.evaluate(context));
    };
    /**
     *  This methods returns the FlattenedProduct. 
     *  @param productInstance
     *  @return
     */
    prototype.getFlattenedProduct = function(productInstance, configuredProduct) {
        var flattenedProduct = new FlattenedProduct();
        var flattenedPageException = null;
        var pageException = null;
        var pageExeIt = null;
        var propertiesMap = null;
        var flattenedPageExceptionList = new ArrayList();
        flattenedProduct.setId(productInstance.getId());
        flattenedProduct.setVersion(productInstance.getVersion());
        flattenedProduct.setUserProductName(productInstance.getUserProductName());
        flattenedProduct.setInstanceId(productInstance.getInstanceId());
        flattenedProduct.setQty(productInstance.getQty());
        flattenedProduct.setProperties(this.buildPropertyMapFromProduct(productInstance));
        flattenedProduct.setContents(productInstance.getContentAssociations());
        pageExeIt = productInstance.getPageExceptions().iterator();
         while (pageExeIt.hasNext()){
            flattenedPageException = new FlattenedPageException();
            pageException = pageExeIt.next();
            flattenedPageException.setHasContent(pageException.isHasContent());
            flattenedPageException.setName(pageException.getName());
            flattenedPageException.setRanges(pageException.getRanges());
            var pePropIt = pageException.getProperties().iterator();
             while (pePropIt.hasNext()){
                var peProp = pePropIt.next();
                if (peProp.getName() == "EXCEPTION_TYPE") {
                    flattenedPageException.setName(peProp.getValue());
                }
            }
            if (flattenedPageException.getName() == "PRINTING_EXCEPTION") {
                var flattenedProperties = new ArrayMap();
                var configredPeList = configuredProduct.getPageExceptions();
                var configuredPeIt = configredPeList.iterator();
                 while (configuredPeIt.hasNext()){
                    var printingExcep = false;
                    var configuredPe = configuredPeIt.next();
                    var configPropIt = configuredPe.getProperties().iterator();
                     while (configPropIt.hasNext()){
                        var peConfigProp = configPropIt.next();
                        if (peConfigProp.getValue() == "PRINTING_EXCEPTION") {
                            printingExcep = true;
                        }
                    }
                    if (printingExcep) {
                        var featureIt = configuredPe.getFeatures().iterator();
                         while (featureIt.hasNext()){
                            var configuredFeature = featureIt.next();
                            var featureInst = productInstance.getFeatureById(configuredFeature.getId());
                            var propIt = featureInst.getChoice().getProperties().iterator();
                             while (propIt.hasNext()){
                                var propInst = propIt.next();
                                flattenedProperties.put(propInst.getName(), propInst.getValue());
                            }
                        }
                    }
                }
                var peFeatureInst = pageException.getFeatures().iterator();
                 while (peFeatureInst.hasNext()){
                    var featureInst = peFeatureInst.next();
                    var pePropInstIt = featureInst.getChoice().getProperties().iterator();
                     while (pePropInstIt.hasNext()){
                        var pePropInst = pePropInstIt.next();
                        flattenedProperties.remove(pePropInst.getName());
                        flattenedProperties.put(pePropInst.getName(), pePropInst.getValue());
                    }
                }
                flattenedPageException.setProperties(flattenedProperties);
            } else {
                flattenedPageException.setProperties(this.buildPropertyMapFromFeature(pageException.getFeatures()));
            }
            flattenedPageExceptionList.add(flattenedPageException);
        }
        flattenedProduct.setPageExceptions(flattenedPageExceptionList);
        return flattenedProduct;
    };
    /**
     *  Build the pageRange for the old Product with tabs and Inserts.After Client adopts to API V2,this methods
     *  will be removed.
     */
    prototype.buildPageRange = function(startPage) {
        var pageRange = new PageRange();
        pageRange.setStart(startPage);
        pageRange.setEnd(startPage);
        var pageRangeList = new ArrayList();
        pageRangeList.add(pageRange);
        return pageRangeList;
    };
    /**
     *  Build the Property map with property name and value from Product Instance.
     */
    prototype.buildPropertyMapFromProduct = function(productInstance) {
        var flattenedProperties = new ArrayMap();
        var property = null;
        var pit = productInstance.getProperties().iterator();
         while (pit.hasNext()){
            property = pit.next();
            flattenedProperties.put(property.getName(), property.getValue());
        }
        flattenedProperties.putAll(this.buildPropertyMapFromFeature(productInstance.getFeatures()));
        return flattenedProperties;
    };
    /**
     *  Build flattened Property map with property name and value for PageException
     *  
     */
    prototype.buildPropertyMapFromFeature = function(featureInstance) {
        var flattenedProperties = new ArrayMap();
        var ci = null;
        var pi = null;
        var fit = featureInstance.iterator();
         while (fit.hasNext()){
            ci = fit.next().getChoice();
            if (ci != null) {
                var pit = ci.getProperties().iterator();
                 while (pit.hasNext()){
                    pi = pit.next();
                    flattenedProperties.put(pi.getName(), pi.getValue());
                }
            }
        }
        return flattenedProperties;
    };
    /**
     * Configure Page Exception
     *  @throws RuleConfigurationException 
     */
    prototype.getConfiguredPageException = function(pe, productRules, context, cfgFeatures) {
        var cpe = new ConfiguredPageException();
        var cf = null;
        cpe.setId(pe.getId());
        cpe.setName(pe.getName());
        var configuredProperties = new ArraySet();
        var it = pe.getProperties().iterator();
        var property = null;
        var printingException = false;
         while (it.hasNext()){
            property = it.next();
            configuredProperties.add(this.getConfiguredProperty(property, productRules, context));
            if (property.getValue() == "PRINTING_EXCEPTION") {
                printingException = true;
            }
        }
        cpe.setProperties(configuredProperties);
        var configFeatureList = new ArraySet();
        var fIt = pe.getFeatures().iterator();
         while (fIt.hasNext()){
            cf = this.getConfiguredFeature(fIt.next(), productRules, context, null, null);
            configFeatureList.add(cf);
        }
        cpe.setFeatures(configFeatureList);
        var frIt = pe.getFeatureRefs().iterator();
        var choiceIdIt = null;
         while (frIt.hasNext()){
            var featureRef = frIt.next();
            var cfgFeature = Utils.getElementById(featureRef.getFeatureId(), cfgFeatures);
            cf = new ConfiguredFeature();
            cf.setId(cfgFeature.getId());
            cf.setName(cfgFeature.getName());
            cf.setSelectable(cfgFeature.isSelectable());
            var peFi = context.getProduct().getFeatureById(cfgFeature.getId());
            if (printingException && peFi != null && featureRef.getChoiceIds().contains(peFi.getChoice().getId())) {
                cf.setDefaultChoiceId(peFi.getChoice().getId());
            } else {
                cf.setDefaultChoiceId(featureRef.getDefaultChoiceId());
            }
            cf.setChoiceRequired(cfgFeature.isChoiceRequired());
            cf.setOverrideWithDefault(cfgFeature.isOverrideWithDefault());
            choiceIdIt = featureRef.getChoiceIds().iterator();
             while (choiceIdIt.hasNext()){
                cf.getChoices().add(cfgFeature.getChoiceById(choiceIdIt.next()));
            }
            cpe.getFeatures().add(cf);
        }
        try {
            cpe.setSelectable(this.isEntitySelectable(pe.getId(), productRules, context, true));
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
        return cpe;
    };
    prototype.buildChoiceMap = function(product) {
        var map = new ArrayMap();
        var fit = product.getFeatures().iterator();
        var cit = null;
        var feature = null;
        var choice = null;
        var pe = null;
         while (fit.hasNext()){
            feature = fit.next();
            cit = feature.getChoices().iterator();
             while (cit.hasNext()){
                choice = cit.next();
                map.put(choice.getId(), choice);
            }
        }
        return map;
    };
    /**
     *  validates PageException start and end range based on the validation rule
     *  defined
     *  
     *  @param productRules
     *  @param context
     *  @return
     *  @throws ProductConfigProcessorException
     */
    prototype.validatePageExceptionPosition = function(productRules, context) {
        var result = null;
        try {
            var rules = productRules.getRulesByRefIdAndType(context.getProduct().getId(), RuleType.VALIDATION);
            var it = rules.iterator();
             while (it.hasNext()){
                var valRule = it.next().getDef();
                result = valRule.evaluate(context);
            }
        }catch (e) {
             throw new ProductConfigProcessorException(Utils.getExceptionMessage(e), e);
        }
        return result;
    };
    prototype.resolveProductId = function(ph) {
        if (ph.getProductId() != null && ph.getProductId() != 0) {
            return ph.getProductId();
        }
        return (1456773326927);
    };
    /**
     *  This method adds the feature to pageException instance for display purpose,if feature at document and file level is the same.
     *  @param cfgPe
     *  @param selectedPe
     *  @param pi
     *  @return
     */
    prototype.addProductLevelFeature = function(cfgPe, peInstance, pi) {
        var updatedPeInstance = new PageExceptionInstance();
        updatedPeInstance.setId(peInstance.getId());
        updatedPeInstance.setInstanceId(peInstance.getInstanceId());
        updatedPeInstance.setProperties(peInstance.getProperties());
        updatedPeInstance.setRanges(peInstance.getRanges());
        updatedPeInstance.setHasContent(peInstance.isHasContent());
        var hasFeature = false;
        var peFeatureList = new ArraySet();
        var cfgIt = cfgPe.getFeatures().iterator();
         while (cfgIt.hasNext()){
            hasFeature = false;
            var cfg = cfgIt.next();
            var peFeatureIt = peInstance.getFeatures().iterator();
             while (peFeatureIt.hasNext()){
                var peFeature = peFeatureIt.next();
                if (peFeature.getId() == cfg.getId()) {
                    hasFeature = true;
                    peFeatureList.add(peFeature);
                    break;
                }
            }
            if (!hasFeature) {
                var featureIt = pi.getFeatures().iterator();
                 while (featureIt.hasNext()){
                    var feature = featureIt.next();
                    if (feature.getId() == cfg.getId()) {
                        peFeatureList.add(feature);
                        break;
                    }
                }
            }
        }
        updatedPeInstance.setFeatures(peFeatureList);
        return updatedPeInstance;
    };
    /**
     *  This method builds pageRange for PageException.
     *  @param pei
     *  @param newStart
     *  @param newEnd 
     *  @return
     */
    prototype.updatePageRange = function(pei, newStart, newEnd) {
        var ranges = new ArrayList();
        var pageRange = new PageRange();
        pageRange.setStart(newStart);
        pageRange.setEnd(newEnd);
        ranges.add(pageRange);
        pei.setRanges(ranges);
        return pei;
    };
    /**
     *  This method splits page range for printing exception if there are tabs/Inserts.
     *  @param pi
     *  @return
     */
    prototype.updatePrintingExceptionPageRange = function(pi) {
        var ca = null;
        var pageRanges = new ArrayList();
        var physicalPageRanges = new ArrayList();
        var pageExceptionInstanceList = new ArrayList();
        var pageCount = 0;
        var pageRange = null;
        var pg = null;
        var peInstanceIt = pi.getPageExceptions().iterator();
        var sortedStarts = new ArrayList();
         while (peInstanceIt.hasNext()){
            var peInstance = peInstanceIt.next();
            var propIterator = peInstance.getProperties().iterator();
             while (propIterator.hasNext()){
                var propInstance = propIterator.next();
                if ((propInstance.getValue().equals(PageExceptionType.TAB.name())) || (propInstance.getValue().equals(PageExceptionType.INSERT.name()))) {
                    var prIterator = peInstance.getRanges().iterator();
                     while (prIterator.hasNext()){
                        var index = 0;
                        var pr = prIterator.next();
                        for (var i = 0; i < sortedStarts.size(); i++) {
                            var start = sortedStarts.get(i);
                            if (pr.getStart() < start) {
                                break;
                            }
                            index++;
                        }
                        Utils.addIntegerToList(index, pr.getStart(), sortedStarts);
                    }
                } else if (propInstance.getValue() == "PRINTING_EXCEPTION") {
                    var prIterator = peInstance.getRanges().iterator();
                     while (prIterator.hasNext()){
                        var range = prIterator.next();
                        pageRanges.add(range);
                    }
                }
            }
        }
        var pageNumber = 0;
        var index = 0;
        var range1 = null;
        var range2 = null;
        var range3 = null;
        var sortedIt = sortedStarts.iterator();
         while (sortedIt.hasNext()){
            index = 0;
            pageNumber = sortedIt.next();
            var pgItr = pageRanges.iterator();
             while (pgItr.hasNext()){
                var range = pgItr.next();
                if (pageNumber == range.getStart()) {
                    range1 = new PageRange();
                    range1.setStart(range.getStart() + 1);
                    range1.setEnd(range.getStart() + 1);
                    range2 = new PageRange();
                    range2.setStart(0);
                    range2.setEnd(0);
                    range3 = new PageRange();
                    range3.setStart(range.getStart() + 1);
                    range3.setEnd(range.getEnd());
                    pageRanges.remove(index);
                    Utils.addRangeToList(index, range2, pageRanges);
                    Utils.addRangeToList(index, range3, pageRanges);
                    break;
                } else if (pageNumber > range.getStart() && pageNumber < range.getEnd()) {
                    range1 = new PageRange();
                    range1.setStart(range.getStart());
                    range1.setEnd(pageNumber - 1);
                    range2 = new PageRange();
                    range2.setStart(0);
                    range2.setEnd(0);
                    range3 = new PageRange();
                    range3.setStart(pageNumber + 1);
                    range3.setEnd(range.getEnd());
                    pageRanges.remove(index);
                    Utils.addRangeToList(index, range3, pageRanges);
                    Utils.addRangeToList(index, range2, pageRanges);
                    Utils.addRangeToList(index, range1, pageRanges);
                    break;
                } else if (pageNumber == range.getEnd()) {
                    range1 = new PageRange();
                    range1.setStart(0);
                    range1.setEnd(0);
                    Utils.addRangeToList(index + 1, range1, pageRanges);
                    break;
                }
                index++;
            }
        }
        for (var i = 0; i < pageRanges.size(); i++) {
            physicalPageRanges.add(pageRanges.get(i).clone());
        }
        var printExceptionIt = pi.getPageExceptions().iterator();
        var pageRangeList = new ArrayList();
        var rangeList = new ArrayList();
         while (printExceptionIt.hasNext()){
            var printException = printExceptionIt.next();
            var printpropIt = printException.getProperties().iterator();
             while (printpropIt.hasNext()){
                var printprop = printpropIt.next();
                if (printprop.getValue() == "PRINTING_EXCEPTION") {
                    var exeRanges = new ArrayList();
                    var prIterator = printException.getRanges().iterator();
                     while (prIterator.hasNext()){
                        var range = prIterator.next();
                        for (var i = 0; i < physicalPageRanges.size(); i++) {
                            if (range.getStart() == physicalPageRanges.get(i).getStart()) {
                                exeRanges.add(pageRanges.get(i).clone());
                                rangeList.add(physicalPageRanges.get(i).getStart());
                                if (range.getEnd() == physicalPageRanges.get(i).getEnd()) {
                                    break;
                                }
                            } else if (range.getStart() < physicalPageRanges.get(i).getStart() && range.getEnd() > physicalPageRanges.get(i).getEnd()) {
                                exeRanges.add(pageRanges.get(i).clone());
                                rangeList.add(physicalPageRanges.get(i).getStart());
                            } else if (range.getEnd() == physicalPageRanges.get(i).getEnd()) {
                                exeRanges.add(pageRanges.get(i).clone());
                                rangeList.add(physicalPageRanges.get(i).getStart());
                                break;
                            }
                        }
                    }
                    printException.setRanges(exeRanges);
                    pageExceptionInstanceList.add(printException);
                } else {
                    Utils.addPageExceptionToList(0, printException, pageExceptionInstanceList);
                }
            }
        }
        var sortedPageExceptionInstList = new ArrayList();
        var start = 0;
        var sortedIndex = 0;
        for (var i = 0; i < pageExceptionInstanceList.size(); i++) {
            start = pageExceptionInstanceList.get(i).getRanges().get(0).getStart();
            var pePropInstIt = pageExceptionInstanceList.get(i).getProperties().iterator();
             while (pePropInstIt.hasNext()){
                var propInst = pePropInstIt.next();
                if (propInst.getName() == "EXCEPTION_TYPE") {
                    if (propInst.getValue() != "PRINTING_EXCEPTION") {
                        var peIndex = 0;
                        for (var j = 0; j < sortedPageExceptionInstList.size(); j++) {
                            var pageRangeStart = sortedPageExceptionInstList.get(j).getRanges().get(0).getStart();
                            if (start < pageRangeStart) {
                                break;
                            }
                            peIndex++;
                        }
                        Utils.addPageExceptionToList(peIndex, pageExceptionInstanceList.get(i), sortedPageExceptionInstList);
                    } else {
                        Utils.addPageExceptionToList(sortedIndex, pageExceptionInstanceList.get(i), sortedPageExceptionInstList);
                    }
                }
            }
            sortedIndex++;
        }
        return sortedPageExceptionInstList;
    };
    prototype.removeChoiceFromInstance = function(choice, feature, featureContainer) {
        var featureInstance = featureContainer.getFeatureById(feature.getId());
        if (featureInstance != null) {
            featureContainer.removeFeature(featureInstance);
        }
    };
}, {});

var ConfiguratorProductData = function() {
    AbstractProductData.call(this);
};
stjs.extend(ConfiguratorProductData, AbstractProductData, [], null, {product: "Product", rules: "ProductRules", displays: "ProductDisplays", presets: {name: "Set", arguments: ["Preset"]}, designTemplates: {name: "List", arguments: ["DesignTemplate"]}});

  exports.AbstractRuleElement = AbstractRuleElement;
  exports.ProductElement = ProductElement;
  exports.RuleDefinition = RuleDefinition;
  exports.ValueType = ValueType;
  exports.DisplayHint = DisplayHint;
  exports.RuleConfigurationException = RuleConfigurationException;
  exports.ProductConfigProcessorException = ProductConfigProcessorException;
  exports.InsertPosition = InsertPosition;
  exports.FeatureInstanceContainer = FeatureInstanceContainer;
  exports.PresetChoice = PresetChoice;
  exports.ElementType = ElementType;
  exports.ValidationSeverity = ValidationSeverity;
  exports.PropertyInputDetailsValue = PropertyInputDetailsValue;
  exports.CalcOperator = CalcOperator;
  exports.ValidationResultCode = ValidationResultCode;
  exports.ValueProvider = ValueProvider;
  exports.ProductContext = ProductContext;
  exports.ContentDimensions = ContentDimensions;
  exports.ValidationResultProvider = ValidationResultProvider;
  exports.PropertyAllowedValue = PropertyAllowedValue;
  exports.ProductReference = ProductReference;
  exports.CompatibilitySubGroup = CompatibilitySubGroup;
  exports.ContentOrientation = ContentOrientation;
  exports.PageRange = PageRange;
  exports.CatalogReference = CatalogReference;
  exports.ValidationType = ValidationType;
  exports.ComparisonOperator = ComparisonOperator;
  exports.TextCharacterization = TextCharacterization;
  exports.DataType = DataType;
  exports.AbstractCondition = AbstractCondition;
  exports.TrueCondition = TrueCondition;
  exports.DisplayEntry = DisplayEntry;
  exports.DisplayText = DisplayText;
  exports.FeatureReference = FeatureReference;
  exports.AbstractProperty = AbstractProperty;
  exports.AbstractFeature = AbstractFeature;
  exports.AbstractChoice = AbstractChoice;
  exports.AbstractPageException = AbstractPageException;
  exports.AbstractProduct = AbstractProduct;
  exports.OrderedBooleanRuleDef = OrderedBooleanRuleDef;
  exports.BooleanRuleDef = BooleanRuleDef;
  exports.ValueException = ValueException;
  exports.ConditionException = ConditionException;
  exports.RuleType = RuleType;
  exports.Preset = Preset;
  exports.ProductInstanceDetails = ProductInstanceDetails;
  exports.ValidationResult = ValidationResult;
  exports.ValidationResultMappings = ValidationResultMappings;
  exports.PageExceptionCondition = PageExceptionCondition;
  exports.BooleanOperator = BooleanOperator;
  exports.ConditionalValue = ConditionalValue;
  exports.AbstractValueProvider = AbstractValueProvider;
  exports.ContentRequirement = ContentRequirement;
  exports.ValidationRuleDef = ValidationRuleDef;
  exports.DisplayVersion = DisplayVersion;
  exports.CompatibilityGroup = CompatibilityGroup;
  exports.PageGroup = PageGroup;
  exports.FlattenedPageException = FlattenedPageException;
  exports.AbstractValidationResultProvider = AbstractValidationResultProvider;
  exports.Bound = Bound;
  exports.IdListCondition = IdListCondition;
  exports.DisplayDetails = DisplayDetails;
  exports.PropertyInstance = PropertyInstance;
  exports.Feature = Feature;
  exports.ChoiceInstance = ChoiceInstance;
  exports.PageException = PageException;
  exports.ValueConverter = ValueConverter;
  exports.UnsupportedOperatorException = UnsupportedOperatorException;
  exports.Rule = Rule;
  exports.RuleMappings = RuleMappings;
  exports.ProductInstanceSummary = ProductInstanceSummary;
  exports.ValueRuleDef = ValueRuleDef;
  exports.FeatureChoiceNameProvider = FeatureChoiceNameProvider;
  exports.FeatureChoiceIdProvider = FeatureChoiceIdProvider;
  exports.ContentAssociationProvider = ContentAssociationProvider;
  exports.PropertyValueLengthProvider = PropertyValueLengthProvider;
  exports.TextConcatenationProvider = TextConcatenationProvider;
  exports.SkuReferenceValueProvider = SkuReferenceValueProvider;
  exports.ProductContextMapKeyProvider = ProductContextMapKeyProvider;
  exports.Product = Product;
  exports.Choice = Choice;
  exports.ContentValidationResult = ContentValidationResult;
  exports.ContentAssociation = ContentAssociation;
  exports.ProductionContentAssociation = ProductionContentAssociation;
  exports.DynamicValidationResultProvider = DynamicValidationResultProvider;
  exports.StaticValidationResultProvider = StaticValidationResultProvider;
  exports.Property = Property;
  exports.PropertyInputDetails = PropertyInputDetails;
  exports.EntryDisplayDetails = EntryDisplayDetails;
  exports.ProductDisplayDetails = ProductDisplayDetails;
  exports.ConfiguredFeature = ConfiguredFeature;
  exports.FeatureInstance = FeatureInstance;
  exports.ConfiguredPageException = ConfiguredPageException;
  exports.ContentAssociationCountProvider = ContentAssociationCountProvider;
  exports.ContextContentProvider = ContextContentProvider;
  exports.PropertyValueProvider = PropertyValueProvider;
  exports.CalcValueProvider = CalcValueProvider;
  exports.ProductQtyProvider = ProductQtyProvider;
  exports.ContentSILengthProvider = ContentSILengthProvider;
  exports.ChoiceProvider = ChoiceProvider;
  exports.ProductContextMapValueProvider = ProductContextMapValueProvider;
  exports.ProductIdValueProvider = ProductIdValueProvider;
  exports.PageCountProvider = PageCountProvider;
  exports.TotalPageCountProvider = TotalPageCountProvider; 
  exports.StaticValueProvider = StaticValueProvider;
  exports.ContextChoiceProvider = ContextChoiceProvider;
  exports.ChoicesCondition = ChoicesCondition;
  exports.ContentCondition = ContentCondition;
  exports.ContentAddedCondition = ContentAddedCondition;
  exports.ComparisonCondition = ComparisonCondition;
  exports.GroupCondition = GroupCondition;
  exports.DefaultOverrideChoiceCondition = DefaultOverrideChoiceCondition;
  exports.LastSelectedChoicesCondition = LastSelectedChoicesCondition;
  exports.ProductRules = ProductRules;
  exports.ContentFirstWidthProvider = ContentFirstWidthProvider;
  exports.ContentFirstHeightProvider = ContentFirstHeightProvider;
  exports.PrintReadyStatusProvider = PrintReadyStatusProvider;
  exports.ContentFirstOrientationProvider = ContentFirstOrientationProvider;
  exports.ConfiguredProduct = ConfiguredProduct;
  exports.ConfiguredChoice = ConfiguredChoice;
  exports.ConfiguredProperty = ConfiguredProperty;
  exports.FlattenedProduct = FlattenedProduct;
  exports.DisplayHierarchy = DisplayHierarchy;
  exports.ProductDisplays = ProductDisplays;
  exports.PageExceptionInstance = PageExceptionInstance;
  exports.AbstractProductData = AbstractProductData;
  exports.ProductInstance = ProductInstance;
  exports.ConfiguratorProductData = ConfiguratorProductData;
  exports.DefaultProductContext = DefaultProductContext;
  exports.ProductConfigurationProcessor = ProductConfigurationProcessor;
  exports.PageExceptionValidationProvider = PageExceptionValidationProvider;
  exports.PageExceptionStatusProvider = PageExceptionStatusProvider;
  exports.PageExceptionTotalPageCountProvider = PageExceptionTotalPageCountProvider;
  exports.PageExceptionPageCountProvider = PageExceptionPageCountProvider;
  exports.PageExceptionIdProvider = PageExceptionIdProvider;
  exports.CalcMultiValueProvider = CalcMultiValueProvider;
  exports.CalcMultiConditionalValueProvider = CalcMultiConditionalValueProvider;
  exports.GrommetTopEdgeCountProvider = GrommetTopEdgeCountProvider;
  exports.ProductDisplayProcessor = ProductDisplayProcessor;
  exports.ProductDisplayProcessorException = ProductDisplayProcessorException;  
  exports.DisplayGroup = DisplayGroup;
  exports.ProductHint = ProductHint;
  exports.ContentHint = ContentHint;
  exports.PageExceptionType = PageExceptionType;
  exports.PageType = PageType;
  exports.Collections = Collections;
  exports.ArrayIterator = ArrayIterator;
  exports.ArraySet = ArraySet;
  exports.ArrayMap = ArrayMap;
  exports.ArrayList = ArrayList;
  exports.Utils = Utils;
  exports.JSONModelParser = JSONModelParser;
  exports.ElementDisplay = ElementDisplay;
  exports.PropertyDisplay = PropertyDisplay;
  exports.ExternalSku = ExternalSku;
  exports.ExternalProductionDetails = ExternalProductionDetails;
  exports.ExternalProductionWeight = ExternalProductionWeight;
  exports.ExternalProductionTime = ExternalProductionTime;
  exports.SkuDisplayDetails = SkuDisplayDetails;
  exports.ValueDisplayDetails = ValueDisplayDetails
  exports.WeightUnit = WeightUnit;
  exports.TimeUnit = TimeUnit;
  exports.ContextKeysCondition = ContextKeysCondition;
  exports.DisplayValueType = DisplayValueType;
  exports.ExternalRequirements = ExternalRequirements;
  exports.BleedRange = BleedRange;
  exports.BleedDimension = BleedDimension;
  exports.DesignTemplate = DesignTemplate;
  exports.DesignVendorCode = DesignVendorCode;
  exports.Template = Template;

  if (window && window !== exports) {
    for (var key in exports) {
      if (exports.hasOwnProperty(key)) {
        window[key] = exports[key];
      }
    }
  }

  Object.defineProperty(exports, '__esModule', { value: true });
})));
});