1. Use FTP client (such as Filezilla, WinSCP, cuteFtp) to upload or copy all folders in the zip package to your Magento site root folder. This will not overwrite any existing files, just add new files to the folder structure.


2. After uploading is done, log in your Magento administration panel to refresh cache. Go to System/Cache Management. Select all caches and take action “Refresh” then submit.


3. Navigate to System/Configuration, if you can see the extension tab, it is installed properly.


4. Now if you get Access denied error when click on the extension tab, you need to log out admin panel and log in again.


5. Go to front-end and try to process, make sure that everything is OK