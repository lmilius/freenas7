--- src/lib/HTTP/HTTPMessage.cpp.orig	2008-05-25 06:40:31.000000000 +0200
+++ src/lib/HTTP/HTTPMessage.cpp	2008-05-25 12:34:21.000000000 +0200
@@ -778,7 +778,7 @@
   if(rxContentLength.Search(p_sMessage.c_str()))
   {
     string sContentLength = rxContentLength.Match(1);    
-    m_nContentLength = std::atoll(sContentLength.c_str());
+    m_nContentLength = atoll(sContentLength.c_str());
   }
   
   if((unsigned int)m_nContentLength >= p_sMessage.length())                      
