import os
import re
import csv

ROOT = r"c:\xampp\htdocs\dms"
MIG_DIR = os.path.join(ROOT, 'database', 'migrations')
MODEL_DIR = os.path.join(ROOT, 'app', 'Models')
REPORT_DIR = os.path.join(ROOT, 'reports')
os.makedirs(REPORT_DIR, exist_ok=True)

schema_create_re = re.compile(r"Schema::create\(\s*'(?P<table>[^']+)'\s*,")
col_re = re.compile(r"\$table->\s*([a-zA-Z_]+)\(\s*'([^']+)'(?:\s*,\s*([^\)]+))?")
morphs_re = re.compile(r"\$table->(?:nullableMorphs|morphs)\(\s*'([^']+)'\s*[,)]")

migrations = {}
for root, dirs, files in os.walk(MIG_DIR):
    for fname in files:
        if not fname.endswith('.php'):
            continue
        path = os.path.join(root, fname)
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            src = f.read()
        idx = 0
        while True:
            m = schema_create_re.search(src, idx)
            if not m:
                break
            table = m.group('table')
            start = m.end()
            end_marker = src.find('});', start)
            block = src[start:end_marker] if end_marker!=-1 else src[start: start+4000]
            cols = {}
            for mm in col_re.finditer(block):
                typ = mm.group(1)
                name = mm.group(2)
                extra = mm.group(3) or ''
                cols[name] = {'type': typ, 'extra': extra.strip()}
            for mm in morphs_re.finditer(block):
                base = mm.group(1)
                cols[base + '_type'] = {'type': 'string', 'extra': ''}
                cols[base + '_id'] = {'type': 'unsignedBigInteger', 'extra': ''}
            migrations[table] = cols
            idx = end_marker + 3 if end_marker!=-1 else start

models = {}
for dirpath, dirnames, filenames in os.walk(MODEL_DIR):
    for fn in filenames:
        if not fn.endswith('.php'):
            continue
        path = os.path.join(dirpath, fn)
        rel = os.path.relpath(path, MODEL_DIR).replace('\\','/')
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            src = f.read()
        tmatch = re.search(r"protected\s+\$table\s*=\s*'([^']+)'", src)
        if tmatch:
            tab = tmatch.group(1)
        else:
            classname = os.path.splitext(fn)[0]
            s1 = re.sub('(.)([A-Z][a-z]+)', r'\\1_\\2', classname)
            snake = re.sub('([a-z0-9])([A-Z])', r'\\1_\\2', s1).lower()
            tab = snake + 's'
        fill = []
        fmatch = re.search(r"protected\s+\$fillable\s*=\s*\[([\s\S]*?)\];", src)
        if fmatch:
            body = fmatch.group(1)
            for line in body.splitlines():
                line = line.strip().rstrip(',')
                m2 = re.match(r"'([^']+)'", line)
                if m2:
                    fill.append(m2.group(1))
        casts = {}
        cmatch = re.search(r"protected\s+\$casts\s*=\s*\[([\s\S]*?)\];", src)
        if cmatch:
            body = cmatch.group(1)
            for line in body.splitlines():
                line = line.strip().rstrip(',')
                m3 = re.match(r"'([^']+)'\s*=>\s*'([^']+)'", line)
                if m3:
                    casts[m3.group(1)] = m3.group(2)
        models.setdefault(tab, []).append({
            'file': os.path.join('app','Models', rel),
            'class_file': fn,
            'fillable': fill,
            'casts': casts,
        })

csv_path = os.path.join(REPORT_DIR, 'migration_model_report.csv')
with open(csv_path, 'w', newline='', encoding='utf-8') as csvfile:
    writer = csv.writer(csvfile)
    writer.writerow(['table','migration_columns','model_files','model_fillable_union','model_casts_union','missing_in_model','extra_in_model','notes'])
    all_tables = set(list(migrations.keys()) + list(models.keys()))
    for table in sorted(all_tables):
        mig_cols = sorted(migrations.get(table, {}).keys())
        model_entries = models.get(table, [])
        model_files = ';'.join([m['file'] for m in model_entries])
        fillable_union = set()
        casts_union = {}
        for m in model_entries:
            fillable_union.update(m['fillable'])
            casts_union.update(m['casts'])
        fillable_list = sorted(fillable_union)
        casts_list = ';'.join([f"{k}:{v}" for k,v in casts_union.items()])
        missing = sorted([c for c in mig_cols if c not in fillable_union])
        extra = sorted([f for f in fillable_list if f not in mig_cols])
        notes = []
        if table not in migrations:
            notes.append('NO_MIGRATION_FOUND')
        if table not in models:
            notes.append('NO_MODEL_FOUND')
        writer.writerow([
            table,
            '|'.join(mig_cols),
            model_files,
            '|'.join(fillable_list),
            casts_list,
            '|'.join(missing),
            '|'.join(extra),
            '|'.join(notes)
        ])

print('WROTE', csv_path)
