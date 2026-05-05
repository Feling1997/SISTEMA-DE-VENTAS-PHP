Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

baseDir = fso.GetParentFolderName(WScript.ScriptFullName)
If Not fso.FileExists(fso.BuildPath(baseDir, "web_app.py")) Then
    If fso.FileExists("C:\Reparaciones\reparaciones_python\web_app.py") Then
        baseDir = "C:\Reparaciones\reparaciones_python"
    End If
End If
If Not fso.FileExists(fso.BuildPath(baseDir, "web_app.py")) Then
    If fso.FileExists("C:\REPARACIONES\reparaciones_python\web_app.py") Then
        baseDir = "C:\REPARACIONES\reparaciones_python"
    End If
End If
shell.CurrentDirectory = baseDir

launcher = fso.BuildPath(baseDir, "iniciar_web_oculto.bat")
comando = """" & launcher & """"
shell.Run comando, 0, False
