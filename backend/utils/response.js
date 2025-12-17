//this file convert te all API responses to standart format


//Send succes response
//res:Node.js response object
//data: the data of response
//message:optional succes message
//statusCode:HTTP status code(default 200)
function sendSuccess(res, data = null , message='Process successful ',statusCode=200){

    res.writeHead(statusCode, {
        'Content-Type':'application/json',
        // * allow all domains response 
        'Access-Control-Allow-Origin':'*'
    });

    const response = {
        success: true,
        message : message
    };

    //if data is exist asigning 
    if(data !== null){
        response.data= data;
    }

    res.end(JSON.stringify(response));
}



// res: Node.js HTTP response object
// message: error messahe
// statusCode: HTTP errpr code (default 400)

// common HTTP error  codes:
// 400 = Bad Request 
// 401 = Unauthorized 
// 403 = Forbidden 
// 404 = Not Found 
// 500 = Internal Server Error 

function sendError(res, message ='An error appear', statusCode =400){
    res.writeHead(statusCode,{
        'Content-Type':'application/json',
        'Access-Control-Allow-Origin': '*'
    });
    const response = {
        success: false,
        error: message
    };

    res.end(JSON.stringify(response));
}

function notFound(res, message = 'page doesnt find') {
    sendError(res, message, 404);
}



// 401 Unauthorized 


function unauthorized(res, message = 'You have login') {
    sendError(res, message, 401);
}



// 403 Forbidden


function forbidden(res, message = 'forbidden access') {
    sendError(res, message, 403);
}


// 500 Server Error 


function serverError(res, message = 'Server error appear') {
    sendError(res, message, 500);
}


//exporting part
module.exports = {
    sendSuccess,
    sendError,
    notFound,
    unauthorized,
    forbidden,
    serverError
};