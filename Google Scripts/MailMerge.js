///////////////////////////////////////////////////////////////////////////////
// Google Mail Merge Script
// 
// Script loops through a list of emails in an associated google sheet, opens
// the referenced document template, reads in the content, replaces designated
// variables with values from the worksheet, and then generates an email
//
// Sample Google Sheet:
//     https://docs.google.com/spreadsheets/d/1flKWCBqCbWvP_g8mIYbcKjm7HrvmpvFIKrGpP49Ow78/edit?usp=sharing
// Sample Google Doc Email Template:
//     https://docs.google.com/document/d/1BSqEw4lP8lAL4GYJKhAViokmETrZI_DvCcY_CtFf1QE/edit?usp=sharing
///////////////////////////////////////////////////////////////////////////////

function sendEmails() {

// Setup objects & variables
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var dataSheet = ss.getSheetByName('Working');
    var sheetHRoffset = 3;
    var numRows = dataSheet.getLastRow() - sheetHRoffset;
    var numCols = dataSheet.getLastColumn();
    var dataHeaders = dataSheet.getRange(sheetHRoffset, 1, 1,numCols).getValues();  
    var workingData = dataSheet.getRange(sheetHRoffset+1, 1, numRows,numCols).getValues();   
    var idxEmail = 4; // working array index
    var idxTemplateName = 5; // working array index
    var idxResponseDate = 8; // original sheet column index
    var idxConfirm = 8; // working array index
    var idxNotes = 10; // original sheet column index
    
// Loop rows
    for (row = 0; row < numRows; row++) {
    
    // Variables
        var originalRow = row + sheetHRoffset + 1;    // current row + document headers + 1 for zero-based array
    
    // Check if message has already been sent
    
        if (isEmpty(workingData[row][idxConfirm])) {
        // Message has not been sent yet
    
        // Check for valid email
            if (workingData[row][4] != "") {
            
            // Configure the email
                // Subject
                  var emailSubject = workingData[row][idxTemplateName];
                // Send Parameters
                  var emailParameters = {
                    htmlBody: getHtmlEmail(workingData[row]),
                    replyTo: "caeryn@gmail.com",
                    cc: "caeryn@gmail.com"
                  };
                
            // Send the email
                MailApp.sendEmail(workingData[row][idxEmail], emailSubject, '', emailParameters);
                
            // Confirm message sent 
                dataSheet.getRange(originalRow, idxConfirm+1, 1, 1).setValue('Yes');
                dataSheet.getRange(originalRow, idxResponseDate, 1, 1).setValue(Date());
            
            } else {
            
            // Error:  invalid email address
                dataSheet.getRange(originalRow, idxNotes, 1, 1).setValue('Invalid Email address');
            
            }
            
        } else {
        // Message already sent
        }
    
    }    

}

function isEmpty(passedValue) {

// Check to see if a passed value is empty/null

    // default:  string is not empty
    var booReturn = false;
    // cast to string
    var str = passedValue.toString();
    // remove leading white space
    str = str.replace(/^\s\s*/, "");
    // remove trailing white space
    str = str.replace(/\s\s*$/, "");
    // check string length
    if (str.length < 1) {
        booReturn = true;
    }
        
    return booReturn;

}

function getHtmlEmail(currentRow) {

// row is an incoming array equivalent to the document rows
    
// Set template variables
    var workingDocId = DriveApp.getFileById(currentRow[6]).makeCopy().getId();
    var workingDoc = DocumentApp.openById(workingDocId);
    var body = workingDoc.getBody().getText();
    
// Set document variables
    var keys = {
        STUDENT_NUMBER: currentRow[1],
        STUDENT_LAST_NAME: currentRow[2],
        STUDENT_FIRST_NAME: currentRow[3],
        VARIABLE_1: currentRow[10],
        VARIABLE_2: currentRow[11],
        VARIABLE_3: currentRow[12],
        VARIABLE_4: currentRow[13],
        VARIABLE_5: currentRow[14],
        VARIABLE_6: currentRow[15],
        VARIABLE_7: currentRow[16],
        VARIABLE_8: currentRow[17],
        VARIABLE_9: currentRow[18],
        VARIABLE_10: currentRow[19],
    };
        
// Replace the document variables
    for ( var k in keys ) { 
        while (body.toString().indexOf("%" + k + "%") > -1) {
            body = body.toString().replace("%" + k + "%", keys[k]);
        };
    }
    
// Finish processing
    
    workingDoc.saveAndClose();
    DriveApp.getFileById(workingDocId).setTrashed(true);

// Return final result
    return body;

}
