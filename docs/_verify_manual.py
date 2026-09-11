import zipfile
path = r'C:\Users\bangn\Documents\Kerja\online_attendance_system\MANUAL_BOOK_Online_Attendance_System.docx'
with zipfile.ZipFile(path) as z:
    with z.open('word/document.xml') as f:
        content = f.read().decode('utf-8')
print('Document size:', len(content), 'chars')
print('Heading1 count:', content.count('w:val="Heading1"'))
print('Heading2 count:', content.count('w:val="Heading2"'))
print('Heading3 count:', content.count('w:val="Heading3"'))
print('Heading4 count:', content.count('w:val="Heading4"'))
print('Tables count:', content.count('<w:tbl>'))
print('Has TOC field:', ' TOC ' in content)
print('Has fldChar:', 'fldChar' in content)
