# Tests for Postman
- [Postman Sandbox Doc](https://www.getpostman.com/docs/sandbox)
- [Postman Sandbox Examples](https://www.getpostman.com/docs/testing_examples)

This is an example of [Blueman](https://github.com/pixelfusion/blueman) file with test scripts for Postman.

`### H3` heading **must** contains [API Blueprint](https://apiblueprint.org)'s [Action Section](https://github.com/apiaryio/api-blueprint/blob/master/API%20Blueprint%20Specification.md#def-action-section) <identifier>: action defined by name, i.e. `## <identifier> [<HTTP request method>]`.

Postman test script **must** be followed by this heading section.

All scripts **must** be started with Markdown code block definition [example](https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet#code).

All test scripts for Actions are optional.

## Initial code
Header `## H2`  above wiil be ignored.

This section **may** by empty or not defined.

Code below will be prepend to all test scripts. **May** be empty.

```javascript
var jsonData = JSON.parse(responseBody);
```

### Create a Player     
This text will be ignored. Use it to comments.

```javascript
tests["Content-Type is present"] = postman.getResponseHeader("Content-Type"); //Note: the getResponseHeader() method returns the header value, if it exists.
tests["Status code is 200"] = responseCode.code === 200;
```

This text also will be ignored.


### Another action
No tests.


### Example 0  
Empty test

```
```


### Example 1
Code without language definition

```
var a = 1;
```


### Example 2
Empty code block with language definition 

```javascript
```