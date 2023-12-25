import axios from 'axios';
import { DownloadIcon, FileArchive } from 'lucide-react';
import React from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from "./ui/table";

export default function Download() {
    const [plugins, setPlugins] = React.useState([]);

    React.useEffect(async () => {
        await getPlugin();
    }, []);

    async function getPlugin() {
        const response = await axios.get('/wp-json/pb/v1/plugins');
        const { data } = response.data;
        setPlugins(data);
    }

    return (
        <>
            {plugins?.length > 0 ? (
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="text-muted-foreground font-normal">Name</TableHead>
                                <TableHead className="text-muted-foreground font-normal">Download</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {plugins?.map((plugin) => (
                                <TableRow key={plugin?.name}>
                                    <TableCell className="font-medium"><div className='flex items-center gap-2'><FileArchive /> {plugin?.name}</div></TableCell>
                                    <TableCell><a href={plugin?.link}><DownloadIcon /></a></TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            ) : (
                <div className="rounded-md border p-4 text-center">All Generated plugins display here.</div>
            )}
        </>
    )
}
